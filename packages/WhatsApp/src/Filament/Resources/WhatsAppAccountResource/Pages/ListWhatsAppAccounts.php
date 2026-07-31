<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource;

final class ListWhatsAppAccounts extends ListRecords
{
    protected static string $resource = WhatsAppAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
