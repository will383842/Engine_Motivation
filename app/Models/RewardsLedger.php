<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardsLedger extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = "rewards_ledger";

    protected $fillable = [
        "chatter_id", "reward_type", "amount", "source",
        "source_id", "description", "granted_by",
    ];

    public function chatter(): BelongsTo
    {
        return $this->belongsTo(Chatter::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, "granted_by");
    }
}
