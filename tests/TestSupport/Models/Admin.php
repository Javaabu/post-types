<?php

namespace Javaabu\PostTypes\Tests\TestSupport\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Mediapicker\Concerns\OwnsMedia;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Permissions\Traits\HasRoles;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Admin extends Authenticatable implements MediaOwner
{
    use HasRoles;
    use InteractsWithMedia;
    use OwnsMedia;

    protected $guarded = [];

    protected string $guard_name = 'web_admin';

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerMediapickerConversions($media);
    }

    public function registerMediaCollections(): void
    {
        $this->registerMediapickerCollections();
    }
}
