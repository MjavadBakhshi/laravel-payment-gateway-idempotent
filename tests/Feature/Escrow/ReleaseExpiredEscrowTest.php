<?php

namespace Tests\Feature\Escrow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Payment\Concrns\PaymentBase;

class ReleaseExpiredEscrowTest extends PaymentBase
{
    use RefreshDatabase;

    #[Test]
    public function it_releases_expired_escrow_with_split_amounts() {}

    #[Test]
    public function it_does_not_release_non_expired_escrow() {}

    #[Test]
    public function it_does_not_release_already_refunded_escrow() {}
}
