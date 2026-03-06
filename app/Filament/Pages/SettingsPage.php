<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('portfolio_fee_percent', $data['portfolio_fee_percent'] ?? 0);
        Setting::set('portfolio_fee_fixed',   $data['portfolio_fee_fixed']   ?? 0);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
