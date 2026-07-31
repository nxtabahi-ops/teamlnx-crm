<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource;

final class EditWhatsAppAccount extends EditRecord
{
    protected static string $resource = WhatsAppAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
