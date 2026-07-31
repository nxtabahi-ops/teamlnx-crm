<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Policies;

use App\Models\User;
use Relaticle\WhatsApp\Models\WhatsAppBroadcast;

final class WhatsAppBroadcastPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsAppBroadcast $whatsAppBroadcast): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WhatsAppBroadcast $whatsAppBroadcast): bool
    {
        return true;
    }

    public function delete(User $user, WhatsAppBroadcast $whatsAppBroadcast): bool
    {
        return true;
    }
}
