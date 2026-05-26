<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Настройки';
    protected static ?string $title = 'Настройки';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'portfolio_fee_percent' => Setting::get('portfolio_fee_percent', 0),
            'portfolio_fee_fixed'   => Setting::get('portfolio_fee_fixed',   0),
            'portfolio_lock_days'   => (int) Setting::get('portfolio_lock_days', 365),
            'staking_enabled'       => (bool) Setting::get('staking_enabled', 1),
            'staking_year_basis_days' => (int) Setting::get('staking_year_basis_days', 365),
            'card_deposit_details'    => (string) Setting::get('card_deposit_details', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Комиссия перевода из портфеля')
                    ->description('Комиссия при переводе пользователем из Портфеля на Торговый счёт')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('portfolio_fee_percent')
                            ->label('Комиссия, %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Процент от суммы перевода. Пример: 1.5 = 1.5%'),
                        TextInput::make('portfolio_fee_fixed')
                            ->label('Фиксированная комиссия')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Фиксированная сумма, удерживается независимо от размера перевода'),
                        TextInput::make('portfolio_lock_days')
                            ->label('Блокировка инвестиции (дней)')
                            ->numeric()
                            ->integer()
                            ->default(365)
                            ->minValue(0)
                            ->helperText('Срок, в течение которого купленный в портфель актив нельзя продать. 365 = 1 год. 0 = без блокировки.'),
                    ])->columns(2),
                Section::make('Стейкинг')
                    ->description('Глобальное поведение стейкинга и расчётная база')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Toggle::make('staking_enabled')
                            ->label('Разрешить стейкинг для пользователей')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Если выключено, пользователи не смогут открывать новые позиции стейкинга.'),
                        TextInput::make('staking_year_basis_days')
                            ->label('База года (дней)')
                            ->numeric()
                            ->integer()
                            ->default(365)
                            ->minValue(1)
                            ->maxValue(366)
                            ->helperText('Используется в формуле начисления: сумма * APY * срок / база'),
                    ])->columns(2),
                Section::make('Депозит по карте')
                    ->description('Текст банковских реквизитов, показывается пользователю на вкладке Депозит → Карта')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Textarea::make('card_deposit_details')
                            ->label('Банковские реквизиты / инструкции')
                            ->rows(8)
                            ->helperText('Обычный текст. Будет показан пользователю как есть.')
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('portfolio_fee_percent', $data['portfolio_fee_percent'] ?? 0);
        Setting::set('portfolio_fee_fixed',   $data['portfolio_fee_fixed']   ?? 0);
        Setting::set('portfolio_lock_days',   (int) ($data['portfolio_lock_days'] ?? 365));
        Setting::set('staking_enabled',       (int) ($data['staking_enabled'] ?? true));
        Setting::set('staking_year_basis_days', (int) ($data['staking_year_basis_days'] ?? 365));
        Setting::set('card_deposit_details',  (string) ($data['card_deposit_details']  ?? ''));

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
