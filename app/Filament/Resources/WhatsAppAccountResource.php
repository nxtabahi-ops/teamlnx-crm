<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\WhatsAppAccount;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WhatsAppAccountResource extends Resource
{
    protected static ?string $model = WhatsAppAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'WhatsApp Settings';

    protected static ?string $navigationLabel = 'WhatsApp Accounts';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('display_name')
                    ->label('Account Display Name')
                    ->placeholder('e.g. Sales Team WhatsApp')
                    ->required(),
                TextInput::make('phone_number')
                    ->label('Phone Number (E.164)')
                    ->placeholder('+15551234567')
                    ->required(),
                TextInput::make('phone_number_id')
                    ->label('Meta Phone Number ID')
                    ->required(),
                TextInput::make('waba_id')
                    ->label('Meta WABA ID')
                    ->required(),
                TextInput::make('access_token')
                    ->label('Permanent Access Token')
                    ->password()
                    ->required(),
                TextInput::make('verify_token')
                    ->label('Webhook Verify Token')
                    ->default(fn () => \Illuminate\Support\Str::random(32))
                    ->required(),
                TextInput::make('app_secret')
                    ->label('Meta App Secret (Optional)')
                    ->password(),
                Toggle::make('is_active')
                    ->label('Account Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->searchable()->sortable(),
                TextColumn::make('phone_number')->searchable(),
                TextColumn::make('phone_number_id')->searchable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }
}
