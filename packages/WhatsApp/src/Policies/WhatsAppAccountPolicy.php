<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Policies;

use App\Models\User;
use Relaticle\WhatsApp\Models\WhatsAppAccount;

final class WhatsAppAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsAppAccount $whatsAppAccount): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WhatsAppAccount $whatsAppAccount): bool
    {
        return true;
    }

    public function delete(User $user, WhatsAppAccount $whatsAppAccount): bool
    {
        return true;
    }
}
