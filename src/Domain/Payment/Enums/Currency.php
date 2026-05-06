<?php

namespace Domain\Payment\Enums;

enum Currency :string
{
    case EUR = 'EUR';
    case USD = 'USD';
}