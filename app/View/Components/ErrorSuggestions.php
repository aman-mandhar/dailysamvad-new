<?php

namespace App\View\Components;

use App\Queries\ErrorPageQuery;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ErrorSuggestions extends Component
{
    public function __construct(private readonly ErrorPageQuery $query) {}

    public function render(): View
    {
        return view('components.errors.suggestions', ['posts' => $this->query->latest()]);
    }
}
