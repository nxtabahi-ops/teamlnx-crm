<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_accounts') && !Schema::hasColumn('whatsapp_accounts', 'creator_id')) {
            Schema::table('whatsapp_accounts', function (Blueprint $table): void {
                $table->foreignUlid('creator_id')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_accounts', 'creator_id')) {
            Schema::table('whatsapp_accounts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('creator_id');
            });
        }
    }
};
