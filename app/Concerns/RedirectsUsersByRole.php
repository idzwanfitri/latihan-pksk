<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

trait RedirectsUsersByRole
{
    /**
     * Where a user belongs after authenticating: admins manage the question
     * bank, everybody else goes to the exam.
     */
    protected function homeFor(?Authenticatable $user): string
    {
        return $user instanceof User && $user->isAdmin()
            ? route('admin.soalan-a')
            : route('ujian.mula');
    }
}
