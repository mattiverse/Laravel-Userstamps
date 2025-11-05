<?php

namespace Mattiverse\Userstamps\Traits;

use Mattiverse\Userstamps\Userstamps;

use Illuminate\Support\Str;

trait UuidManager
{
    public static function generateUUID(): string
    {
        return Str::uuid()->toString();
    }

    public static function resolveAuthenticatedUserId($user): string
    {
        if (is_null($user)) {
            Userstamps::resolveUsing(function () {
                return '';
            });
            return '';
        }

        Userstamps::resolveUsing(function () use ($user) {
            return $user->id;
        });
        return $user->id;
    }
}
