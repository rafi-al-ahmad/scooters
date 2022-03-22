<?php

namespace App\Providers;

use App\Events\ApproveAppointmentEvent;
use App\Events\NewAppointmentEvent;
use App\Listeners\SendApprovedAppointmentNotification;
use App\Listeners\SendCreatedAppointmentNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NewAppointmentEvent::class => [
            SendCreatedAppointmentNotification::class,
        ],
        ApproveAppointmentEvent::class => [
            SendApprovedAppointmentNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
