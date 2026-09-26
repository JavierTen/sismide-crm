<?php

namespace App\Livewire;

use App\Support\YearContext;
use Livewire\Component;

class YearSwitcher extends Component
{
    public string $selectedYear = '';

    public string $currentUrl = '/';

    public function mount(): void
    {
        $this->selectedYear = (string) session(YearContext::SESSION_KEY, (string) now()->year);
        $this->currentUrl = url()->current();
    }

    public function updatedSelectedYear(): void
    {
        session([YearContext::SESSION_KEY => $this->selectedYear]);

        $this->redirect($this->currentUrl);
    }

    public function render()
    {
        $panel = str_starts_with(parse_url($this->currentUrl, PHP_URL_PATH) ?? '', '/eje')
            ? 'eje'
            : 'dashboard';

        return view('livewire.year-switcher', [
            'years' => YearContext::availableYears($panel),
        ]);
    }
}
