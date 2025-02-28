<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleApiTest extends TestCase
{
    public function testHelloWorldEndpoint()
    {
        $response = $this->get("/api/hello");

        $response
          ->assertStatus(200)
          ->assertJson([
            "message" => "Hello, World!",
          ]);
    }
}
