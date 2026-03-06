<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
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
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $title = 'Settings';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'portfolio_fee_percent' => Setting::get('portfolio_fee_percent', 0),
            'portfolio_fee_fixed'   => Setting::get('portfolio_fee_fixed',   0),
            'staking_enabled'       => (bool) Setting::get('staking_enabled', 1),
            'staking_year_basis_days' => (int) Setting::get('staking_year_basis_days', 365),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Portfolio Transfer Fee')
                    ->description('Commission applied when user transfers from Portfolio → Trading Account')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('portfolio_fee_percent')
                            ->label('Fee, %')
                            ->numeric()
                            ->default(0)
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Percentage of transfer amount. Example: 1.5 = 1.5%'),
                        TextInput::make('portfolio_fee_fixed')
                            ->label('Fixed fee')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Fixed amount deducted regardless of transfer size'),
                    ])->columns(2),
                Section::make('Staking')
                    ->description('Global staking behavior and reward basis')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Toggle::make('staking_enabled')
                            ->label('Enable staking for users')
                            ->default(true)
                            ->inline(false)
                            ->helperText('When disabled, users cannot open new staking positions.'),
                        TextInput::make('staking_year_basis_days')
                            ->label('Year basis (days)')
                            ->numeric()
                            ->integer()
                            ->default(365)
                            ->minValue(1)
                            ->maxValue(366)
                            ->helperText('Used in reward formula: amount * APY * duration / basis'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('portfolio_fee_percent', $data['portfolio_fee_percent'] ?? 0);
        Setting::set('portfolio_fee_fixed',   $data['portfolio_fee_fixed']   ?? 0);
        Setting::set('staking_enabled',       (int) ($data['staking_enabled'] ?? true));
        Setting::set('staking_year_basis_days', (int) ($data['staking_year_basis_days'] ?? 365));

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
