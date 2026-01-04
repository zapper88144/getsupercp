<?php

namespace App\Policies;

use App\Models\SslCertificate;
use App\Models\User;

class SslCertificatePolicy
{
    public function view(User $user, SslCertificate $certificate): bool
    {
        return $user->id === $certificate->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SslCertificate $certificate): bool
    {
        return $user->id === $certificate->user_id;
    }

    public function delete(User $user, SslCertificate $certificate): bool
    {
        return $user->id === $certificate->user_id;
    }
}
