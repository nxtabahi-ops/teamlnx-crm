<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Relaticle\WhatsApp\Models\WhatsAppTemplate;

final class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp Settings';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('whatsapp_account_id')
                    ->relationship('account', 'display_name')
                    ->required(),

                TextInput::make('name')
                    ->label('Template Name (Meta Template ID)')
                    ->required(),

                TextInput::make('language')
                    ->default('en_US')
                    ->required(),

                Select::make('category')
                    ->options([
                        'MARKETING' => 'MARKETING',
                        'UTILITY' => 'UTILITY',
                        'AUTHENTICATION' => 'AUTHENTICATION',
                    ])
                    ->required(),

                Select::make('status')
                    ->options([
                        'APPROVED' => 'APPROVED',
                        'PENDING' => 'PENDING',
                        'REJECTED' => 'REJECTED',
                    ])
                    ->default('APPROVED')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('account.display_name')->label('Account'),
                TextColumn::make('language'),
                TextColumn::make('category'),
                TextColumn::make('status'),
                TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
