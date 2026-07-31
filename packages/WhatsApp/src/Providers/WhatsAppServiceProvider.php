<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Relaticle\WhatsApp\Models\WhatsAppAccount;
use Relaticle\WhatsApp\Models\WhatsAppTemplate;
use Relaticle\WhatsApp\Models\WhatsAppBroadcast;
use Relaticle\WhatsApp\Policies\WhatsAppAccountPolicy;
use Relaticle\WhatsApp\Policies\WhatsAppTemplatePolicy;
use Relaticle\WhatsApp\Policies\WhatsAppBroadcastPolicy;

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

        Gate::policy(WhatsAppAccount::class, WhatsAppAccountPolicy::class);
        Gate::policy(WhatsAppTemplate::class, WhatsAppTemplatePolicy::class);
        Gate::policy(WhatsAppBroadcast::class, WhatsAppBroadcastPolicy::class);
    }
}
