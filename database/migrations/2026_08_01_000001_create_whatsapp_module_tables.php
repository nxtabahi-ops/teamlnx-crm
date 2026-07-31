<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. WhatsApp Business Accounts (Multi-number)
        Schema::create('whatsapp_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('phone_number_id', 100);
            $table->string('waba_id', 100);
            $table->string('phone_number', 30);
            $table->string('display_name', 150)->nullable();
            $table->text('access_token'); // Encrypted token
            $table->string('verify_token', 255);
            $table->string('app_secret', 255)->nullable();
            $table->string('webhook_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'phone_number_id']);
        });

        // 2. WhatsApp Contacts (linked to Relaticle People)
        Schema::create('whatsapp_contacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('people_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('wa_id', 50);
            $table->string('phone_number', 30);
            $table->string('profile_name', 150)->nullable();
            $table->string('avatar_url', 255)->nullable();
            $table->json('custom_metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'wa_id']);
            $table->index(['team_id', 'phone_number']);
        });

        // 3. WhatsApp Conversations (Chat threads)
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignUlid('whatsapp_contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignUlid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open'); // open, pending, resolved, archived
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->text('last_message_preview')->nullable();
            $table->timestamp('window_expires_at')->nullable(); // 24-hr session window
            $table->timestamps();

            $table->index(['team_id', 'status', 'last_message_at']);
        });

        // 4. WhatsApp Messages (Inbound & Outbound)
        Schema::create('whatsapp_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('wamid', 255)->nullable()->unique();
            $table->string('direction', 10); // inbound, outbound
            $table->string('sender_type', 10); // contact, user, system, bot
            $table->foreignUlid('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20); // text, image, video, document, audio, voice, sticker, location, contacts, template, interactive
            $table->text('body')->nullable();
            $table->text('media_url')->nullable();
            $table->string('media_mime_type', 100)->nullable();
            $table->string('media_filename', 255)->nullable();
            $table->bigInteger('media_size_bytes')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name', 255)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending'); // sent, delivered, read, failed
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['wamid']);
        });

        // 5. WhatsApp Tags & Pivot
        Schema::create('whatsapp_tags', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 20)->default('#6366f1');
            $table->timestamps();

            $table->unique(['team_id', 'name']);
        });

        Schema::create('whatsapp_conversation_tag', function (Blueprint $table): void {
            $table->foreignUlid('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignUlid('tag_id')->constrained('whatsapp_tags')->cascadeOnDelete();
            $table->primary(['conversation_id', 'tag_id']);
        });

        // 6. WhatsApp Internal Notes
        Schema::create('whatsapp_notes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        // 7. WhatsApp Templates (Meta HSM)
        Schema::create('whatsapp_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('language', 10)->default('en_US');
            $table->string('category', 50); // MARKETING, UTILITY, AUTHENTICATION
            $table->string('status', 20)->default('APPROVED'); // APPROVED, PENDING, REJECTED
            $table->json('components');
            $table->timestamps();
        });

        // 8. WhatsApp Broadcasts
        Schema::create('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignUlid('whatsapp_template_id')->constrained('whatsapp_templates')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('status', 20)->default('draft'); // draft, scheduled, processing, completed, failed
            $table->json('target_tag_ids')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('successful_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 9. WhatsApp Webhook Audit Logs
        Schema::create('whatsapp_webhook_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->string('event_type', 100);
            $table->json('payload');
            $table->string('status', 20)->default('processed');
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_logs');
        Schema::dropIfExists('whatsapp_broadcasts');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_notes');
        Schema::dropIfExists('whatsapp_conversation_tag');
        Schema::dropIfExists('whatsapp_tags');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
