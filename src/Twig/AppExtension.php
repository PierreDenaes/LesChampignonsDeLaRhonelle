<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('alert_class', [$this, 'getAlertClass']),
        ];
    }

    public function getAlertClass(string $label): string
    {
        switch ($label) {
            case 'error':
                return 'danger';
            case 'success':
                return 'success';
            case 'warning':
                return 'warning';
            case 'info':
                return 'info';
            default:
                return 'secondary'; // Classe par défaut
        }
    }
}