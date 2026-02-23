<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Facades\Filament;

class UserGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = "Guide d'utilisation";
    protected static ?string $title = "Guide d'utilisation";
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 999;

    protected static string $view = 'filament.pages.user-guide';

    public string $activeSection = 'getting-started';

    public function setSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function getSections(): array
    {
        $company = Filament::getTenant();

        $sections = [
            'getting-started' => [
                'label' => 'Premiers pas',
                'icon' => 'heroicon-o-rocket-launch',
            ],
            'dashboard' => [
                'label' => 'Tableau de bord',
                'icon' => 'heroicon-o-home',
            ],
            'sales' => [
                'label' => 'Module Ventes',
                'icon' => 'heroicon-o-shopping-cart',
            ],
        ];

        $sections['stock'] = [
            'label' => 'Stocks & Achats',
            'icon' => 'heroicon-o-cube',
        ];

        if ($company?->isModuleEnabled('pos')) {
            $sections['pos'] = [
                'label' => 'Caisse (POS)',
                'icon' => 'heroicon-o-calculator',
            ];
        }

        if ($company?->isModuleEnabled('accounting')) {
            $sections['accounting'] = [
                'label' => 'Comptabilité',
                'icon' => 'heroicon-o-calculator',
            ];
            $sections['banking'] = [
                'label' => 'Banque',
                'icon' => 'heroicon-o-building-library',
            ];
        }

        if ($company?->isModuleEnabled('hr')) {
            $sections['hr'] = [
                'label' => 'Ressources Humaines',
                'icon' => 'heroicon-o-user-group',
            ];
        }

        $sections['admin'] = [
            'label' => 'Administration',
            'icon' => 'heroicon-o-cog-6-tooth',
        ];

        $sections['einvoicing'] = [
            'label' => 'Facturation électronique',
            'icon' => 'heroicon-o-paper-airplane',
        ];

        $sections['appendix'] = [
            'label' => 'Annexes & Glossaire',
            'icon' => 'heroicon-o-book-open',
        ];

        return $sections;
    }
}
