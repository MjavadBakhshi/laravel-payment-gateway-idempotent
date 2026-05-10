<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_changes_payment_from_pending_to_held() {}

    #[Test]
    public function webhook_marks_payment_as_failed() {}

    #[Test]
    public function webhook_ignores_already_processed_payment() {}

    #[Test]
    public function webhook_returns_404_for_unknown_transaction() {}
    
}
