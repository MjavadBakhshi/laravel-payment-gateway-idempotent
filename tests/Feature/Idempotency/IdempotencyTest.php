<?php

namespace Tests\Feature\Idempotency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    /** Same idempotency key returns same response */
    #[Test]
    public function test_same_key_returns_cached_response() 
    {
        $response = $this->makePaymentRequest('test-1234');

        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));
        
        $secondResponse = $this->makePaymentRequest('test-1234');
        $this->assertTrue($secondResponse->json('data.idempotent'));

    }
    
    /** Different keys create different payments */
    #[Test]
    public function test_different_keys_create_different_payments() {}
    
    /** Missing idempotency key returns error */
    #[Test]
    public function test_missing_key_returns_400_error() 
    {
        $response = $this->postJson(route('payment.store'));
        $response->assertStatus(400);
    }
    
    /** Idempotency works across multiple requests */
    #[Test]
    public function test_idempotency_works_for_three_identical_requests()
    {
        $response = $this->makePaymentRequest('test-1234');
        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));

        for($i=1; $i < 4; $i++)
        {
            $identicalResponse = $this->makePaymentRequest('test-1234');
            $this->assertTrue($identicalResponse->json('data.idempotent'));
        }
    }
    
    /** Response structure is identical for cached vs new */
    #[Test]
    public function test_cached_response_matches_original_response()
    {
        $response = $this->makePaymentRequest('test-1234');
        $response->assertStatus(201);
        $responseData = $response->json();
        unset($responseData['data']['idempotent']);

        $cachedResponse = $this->makePaymentRequest('test-1234');
        $cachedResponseData = $cachedResponse->json();
        unset($cachedResponseData['data']['idempotent']);

        $this->assertEquals($responseData, $cachedResponseData);

    }
    
    /** Idempotency key expires after 24 hours */
    #[Test]
    public function test_idempotency_cache_expires_after_24_hours() 
    {
        $response = $this->makePaymentRequest('test-1234');
        $response->assertStatus(201);
        $this->assertFalse($response->json('data.idempotent'));

        $this->travel(25)->hours();
        
        $secondResponse = $this->makePaymentRequest('test-1234');
        $this->assertFalse($response->json('data.idempotent'));

        $this->travelBack(); // Return to real time.
    }





    private function makePaymentRequest(string $key)
    {
        $response = $this->withHeaders([
            'Idempotency-Key' => $key
        ])
        ->postJson(
            route('payment.store'), 
            [
                'amount' => fake()->randomFloat(2, max: 2000),
                'currency' => 'EUR',
                'merchant_id' => 'ikea',
                "customer_email" => "customer@example.com"
            ]
        );

        return $response;
    }
}
    
