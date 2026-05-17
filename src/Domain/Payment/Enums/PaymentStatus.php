<?php

namespace Domain\Payment\Enums;

enum PaymentStatus :string 
{
    case Pending = 'Pending';   // Created, waiting for escrow
    case Held = 'Held';   // Money held in escrow
    case Completed = 'Completed'; // Released to seller
    case Failed = 'Failed';   // Failed
    case Refunded = 'Refunded';   // Refunded to buyer
    case Disputed = 'Disputed';
}