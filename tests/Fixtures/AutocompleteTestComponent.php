<?php

namespace GiacomoMasseroni\TextInputAutocomplete\Tests\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use GiacomoMasseroni\TextInputAutocomplete\Forms\Components\AutocompleteInput;
use Livewire\Component;

class AutocompleteTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                AutocompleteInput::make('country')
                    ->options([['value' => 1, 'label' => 'Spain']]),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
        BLADE;
    }
}
