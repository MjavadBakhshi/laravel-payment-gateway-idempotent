<?php

namespace Tests\Feature\Idempotency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Payment\Concrns\PaymentBase;

class IdempotencyTest extends PaymentBase
{
    use RefreshDatabase;

    /** Same idempotency key returns same response */
    #[Test]
    public function test_same_key_returns_cached_response() 
    {
        $this->idempotencyKey = 'test-1234';
        $response = $this->makePayment($this->validPayload);

        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));
        
        $secondResponse = $this->makePayment($this->validPayload);
        $this->assertTrue($secondResponse->json('data.idempotent'));
    }
    
    /** Missing idempotency key returns error */
    #[Test]
    public function test_missing_key_returns_400_error() 
    {
        $response = $this->postJson(route('api.payment.store'));
        $response->assertStatus(400);
    }
    
    /** Idempotency works across multiple requests */
    #[Test]
    public function test_idempotency_works_for_three_identical_requests()
    {
        $this->idempotencyKey = 'test-1234';

        $response = $this->makePayment($this->validPayload);
        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));
        for($i=1; $i < 4; $i++)
        {
            $identicalResponse = $this->makePayment($this->validPayload);
            $this->assertTrue($identicalResponse->json('data.idempotent'));
        }
    }
    
    /** Response structure is identical for cached vs new */
    #[Test]
    public function test_cached_response_matches_original_response()
    {
        $this->idempotencyKey = 'test-1234';
        $response = $this->makePayment($this->validPayload);
        $response->assertStatus(201);
        $responseData = $response->json();
        unset($responseData['data']['idempotent']);

        $cachedResponse = $this->makePayment($this->validPayload);
        $cachedResponseData = $cachedResponse->json();
        unset($cachedResponseData['data']['idempotent']);

        $this->assertEquals($responseData, $cachedResponseData);

    }
    
    /** Idempotency key expires after 24 hours */
    #[Test]
    public function test_idempotency_cache_expires_after_24_hours() 
    {
        $this->idempotencyKey = 'test-1234';
        $response = $this->makePayment($this->validPayload);
        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));
        
        $this->travel(25)->hours();
    
        $secondResponse = $this->makePayment($this->validPayload);
        $secondResponse->assertJsonPath('data.idempotent', true);
        $secondResponse->assertJsonPath('message', 'The payment has already been processed.');
        $this->travelBack(); // Return to real time.
    }
}
    
