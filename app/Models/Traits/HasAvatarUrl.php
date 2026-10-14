<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Storage;

trait HasAvatarUrl
{
    /**
     * Indica se o usuário possui um avatar válido (arquivo existente no disco).
     */
    public function hasAvatar(): bool
    {
        $avatar = $this->profile?->avatar;

        if (empty($avatar)) {
            return false;
        }

        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return true;
        }

        return Storage::disk('public')->exists($avatar);
    }

    /**
     * URL do avatar, com fallback quando o arquivo não existe
     * (o nome pode estar no banco mas o arquivo já ter sido removido).
     */
    public function getAvatarUrlAttribute(): string
    {
        $avatar = $this->profile?->avatar;

        if (empty($avatar)) {
            return asset('images/user-noavatar.png');
        }

        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }

        if (Storage::disk('public')->exists($avatar)) {
            return asset('storage/' . $avatar);
        }

        return asset('images/user-noavatar.png');
    }
}
