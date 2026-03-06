<?php

namespace App\Providers;

use App\Events\BarUpdated;
use App\Listeners\ProcessOrdersOnBarUpdate;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BarUpdated::class => [
            ProcessOrdersOnBarUpdate::class,
        ],
    ];
}
