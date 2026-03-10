<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CampaignLauncher;
use Tests\TestCase;

class CampaignLaunchTest extends TestCase
{
    public function test_it_exists(): void
    {
        $this->assertTrue(class_exists(App\Services\CampaignLauncher::class));
    }
}
