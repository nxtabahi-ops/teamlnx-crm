<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource;

final class CreateWhatsAppAccount extends CreateRecord
{
    protected static string $resource = WhatsAppAccountResource::class;
}
