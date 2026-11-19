<?php

namespace App\Policies;

use App\Models\MedicineRequest;
use App\Models\User;

/**
 * Requests are private to their owner. Administrators can see them for moderation (push B5).
 */
class MedicineRequestPolicy
{
    public function view(User $user, MedicineRequest $request): bool
    {
        return $request->user_id === $user->id || $user->isAdmin();
    }

    public function update(User $user, MedicineRequest $request): bool
    {
        return $request->user_id === $user->id && $request->status->isEditable();
    }

    public function cancel(User $user, MedicineRequest $request): bool
    {
        return $request->user_id === $user->id && $request->status->isOpen();
    }
}
