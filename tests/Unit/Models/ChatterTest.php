<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Chatter;
use Tests\TestCase;

class ChatterTest extends TestCase
{
    public function test_it_exists(): void
    {
        $this->assertTrue(class_exists(App\Models\Chatter::class));
    }
}
