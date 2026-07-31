<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Providers;

use Illuminate\Support\ServiceProvider;

final class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'whatsapp');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}
