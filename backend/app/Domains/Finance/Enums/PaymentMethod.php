<?php

namespace App\Domains\Finance\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Cash = 'cash';
    case Insurance = 'insurance';
    case Other = 'other';

    /** Name of the method shown to the reception. */
    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::CreditCard => 'Cartão de crédito',
            self::DebitCard => 'Cartão de débito',
            self::Cash => 'Dinheiro',
            self::Insurance => 'Convênio',
            self::Other => 'Outro',
        };
    }
}
