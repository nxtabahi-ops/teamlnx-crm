<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Policies;

use App\Models\User;
use Relaticle\WhatsApp\Models\WhatsAppTemplate;

final class WhatsAppTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WhatsAppTemplate $whatsAppTemplate): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WhatsAppTemplate $whatsAppTemplate): bool
    {
        return true;
    }

    public function delete(User $user, WhatsAppTemplate $whatsAppTemplate): bool
    {
        return true;
    }
}
