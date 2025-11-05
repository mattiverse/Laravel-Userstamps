<?php

namespace Mattiverse\Userstamps\Traits;

use Illuminate\Support\Str;
use Mattiverse\Userstamps\Userstamps;

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
