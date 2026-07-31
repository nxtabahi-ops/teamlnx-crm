<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Jobs\DispatchWhatsAppBroadcastJob;
use App\Models\WhatsAppBroadcast;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WhatsAppBroadcastResource extends Resource
{
    protected static ?string $model = WhatsAppBroadcast::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp Settings';

    protected static ?string $navigationLabel = 'Broadcast Campaigns';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('whatsapp_account_id')
                    ->relationship('account', 'display_name')
                    ->required(),
                Select::make('whatsapp_template_id')
                    ->relationship('template', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Campaign Name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('template.name')->label('Template'),
                TextColumn::make('status'),
                TextColumn::make('total_recipients'),
                TextColumn::make('successful_count')->label('Sent'),
                TextColumn::make('failed_count')->label('Failed'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Action::make('dispatch')
                    ->label('Start Campaign')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppBroadcast $record): void {
                        DispatchWhatsAppBroadcastJob::dispatch($record->id);
                    }),
            ]);
    }
}
