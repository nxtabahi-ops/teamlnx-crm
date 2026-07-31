<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_accounts')) {
            Schema::create('whatsapp_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignUlid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone_number_id', 100);
            $table->string('waba_id', 100);
            $table->string('phone_number', 30);
            $table->string('display_name', 150);
            $table->text('access_token'); // Encrypted attribute cast
            $table->string('verify_token', 255);
            $table->string('app_secret', 255)->nullable();
            $table->string('webhook_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'phone_number_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
