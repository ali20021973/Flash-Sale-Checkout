<?php

namespace App\Repositories;

use App\Models\Hold;

class HoldRepository
{
    /**
     * Create a new hold record
     */
    public function create(array $data): Hold
    {
        return Hold::create($data);
    }

    /**
     * Find active holds that are expired
     */
    public function getExpiredActiveHolds(): Collection
    {
        $now = now();
        return Hold::where('status', 'active')
                    ->where('expires_at', '<', $now)
                    ->get();
    }
    /**
     * Lock and find a hold by ID for update (concurrency-safe)
     */
    public function lockForUpdate(int $id): ?Hold
    {
        return Hold::lockForUpdate()->find($id);
    }
}
