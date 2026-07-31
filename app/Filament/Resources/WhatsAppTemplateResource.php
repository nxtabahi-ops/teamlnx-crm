<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\WhatsAppTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'WhatsApp Settings';

    protected static ?string $navigationLabel = 'Message Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('whatsapp_account_id')
                    ->relationship('account', 'display_name')
                    ->required(),
                TextInput::make('name')
                    ->label('Template Name (Meta ID)')
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
                TextColumn::make('language'),
                TextColumn::make('category'),
                TextColumn::make('status'),
                TextColumn::make('created_at')->dateTime(),
            ]);
    }
}
