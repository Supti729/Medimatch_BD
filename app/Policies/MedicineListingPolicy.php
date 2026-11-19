<?php

namespace App\Policies;

use App\Models\MedicineListing;
use App\Models\User;

/**
 * Who may do what with a listing. Laravel finds this policy automatically for MedicineListing.
 * Moderation by administrators (approve, reject, block) is added in push B5.
 */
class MedicineListingPolicy
{
    /** The owner, and administrators, can open the owner's view of a listing. */
    public function view(User $user, MedicineListing $listing): bool
    {
        return $this->owns($user, $listing) || $user->isAdmin();
    }

    public function update(User $user, MedicineListing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->status->isEditable();
    }

    public function changeStatus(User $user, MedicineListing $listing): bool
    {
        return $this->owns($user, $listing);
    }

    public function delete(User $user, MedicineListing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->status->isDeletable();
    }

    private function owns(User $user, MedicineListing $listing): bool
    {
        return $listing->user_id === $user->id;
    }
}
