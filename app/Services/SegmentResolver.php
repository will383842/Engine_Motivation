<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Chatter;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SegmentResolver
{
    public function resolve(Segment $segment): Collection
    {
        $query = Chatter::where('is_active', true);

        foreach ($segment->rules as $rule) {
            $query = $this->applyRule($query, $rule, $segment->operator);
        }

        $chatters = $query->get();

        $segment->update([
            'cached_count' => $chatters->count(),
            'cached_at' => now(),
        ]);

        return $chatters;
    }

    public function count(Segment $segment): int
    {
        return Cache::remember("segment:{$segment->id}:count", 300, function () use ($segment) {
            return $this->resolve($segment)->count();
        });
    }

    private function applyRule(Builder $query, $rule, string $segmentOperator): Builder
    {
        $method = $segmentOperator === 'or' ? 'orWhere' : 'where';

        return $query->{$method}(function (Builder $q) use ($rule) {
            $field = $rule->field;
            $value = $rule->value;

            match ($rule->operator) {
                'eq' => $q->where($field, $value),
                'neq' => $q->where($field, '!=', $value),
                'gt' => $q->where($field, '>', $value),
                'gte' => $q->where($field, '>=', $value),
                'lt' => $q->where($field, '<', $value),
                'lte' => $q->where($field, '<=', $value),
                'in' => $q->whereIn($field, (array) $value),
                'not_in' => $q->whereNotIn($field, (array) $value),
                'contains' => $q->where($field, 'like', "%{$value}%"),
                'is_null' => $q->whereNull($field),
                'is_not_null' => $q->whereNotNull($field),
                default => $q,
            };
        });
    }
}
