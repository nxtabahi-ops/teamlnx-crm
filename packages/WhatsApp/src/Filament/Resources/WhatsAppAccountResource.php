<?php

declare(strict_types=1);

namespace Relaticle\WhatsApp\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages\CreateWhatsAppAccount;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages\EditWhatsAppAccount;
use Relaticle\WhatsApp\Filament\Resources\WhatsAppAccountResource\Pages\ListWhatsAppAccounts;
use Relaticle\WhatsApp\Models\WhatsAppAccount;

final class WhatsAppAccountResource extends Resource
{
    protected static ?string $model = WhatsAppAccount::class;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp Settings';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meta WhatsApp Cloud API Credentials')
                    ->description('Enter your Meta Developer App & WhatsApp Business Account details.')
                    ->schema([
                        TextInput::make('display_name')
                            ->label('Account Name')
                            ->placeholder('e.g., Support WhatsApp')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('phone_number')
                            ->label('Phone Number (E.164)')
                            ->placeholder('+15551234567')
                            ->required()
                            ->maxLength(30),

                        TextInput::make('phone_number_id')
                            ->label('Meta Phone Number ID')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('waba_id')
                            ->label('WhatsApp Business Account (WABA) ID')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('access_token')
                            ->label('Permanent Access Token')
                            ->password()
                            ->revealable()
                            ->required(),

                        TextInput::make('verify_token')
                            ->label('Webhook Verify Token')
                            ->default(fn () => Str::random(32))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('app_secret')
                            ->label('Meta App Secret (Optional for X-Hub-Signature validation)')
                            ->password()
                            ->revealable()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable(),

                TextColumn::make('phone_number_id')
                    ->label('Phone Number ID')
                    ->copyable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppAccounts::route('/'),
            'create' => CreateWhatsAppAccount::route('/create'),
            'edit' => EditWhatsAppAccount::route('/{record}/edit'),
        ];
    }
}
