<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatterInteracted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly \App\Models\Chatter $chatter,
        public readonly string $interactionType,
        public readonly ?array $metadata = null
    ) {}
}
