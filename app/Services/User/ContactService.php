<?php

namespace App\Services\User;

use App\Models\Contact;
use App\Models\User;

class ContactService
{
    public function getContacts(User $user, array $data): array
    {
        $contactsQuery = $user->contacts()
            ->when($data['type'] ?? null, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($data['query'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%$search%");
            });

        $filteredVolume = (float) (clone $contactsQuery)->sum('volume');
        $contactsPaginator = $contactsQuery->latest()->paginate($data['limit'] ?? 20);
        $totalVolume = (float) $user->contacts()->sum('volume');

        return [
            'contacts'        => $contactsPaginator,
            'filtered_volume' => $filteredVolume,
            'total_volume'    => $totalVolume,
        ];
    }

    public function createContact(User $user, array $data): Contact
    {
        return $user->contacts()->create($data);
    }

    public function updateContact(Contact $contact, array $data): Contact
    {
        $contact->update($data);
        return $contact;
    }

    public function deleteContact(Contact $contact, User $user): ?bool
    {
        if ($contact->user_id !== $user->id) {
            abort(403, 'You do not own this contact.');
        }
        return $contact->delete();
    }
}
