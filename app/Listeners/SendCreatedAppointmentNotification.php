<?php

namespace App\Listeners;

use App\Events\NewAppointmentEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\CreatedAppointmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCreatedAppointmentNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\NewAppointmentEvent  $event
     * @return void
     */
    public function handle(NewAppointmentEvent $event)
    {
        $appointment = Appointment::find($event->appointment->id); 
        $user = $event->user;

        $admins = User::where('is_admin', 1)->get();

        $admins->each(function ($admin) use ($appointment, $user) {
            $admin->notify(new CreatedAppointmentNotification($appointment, $user));
        });
    }
}
