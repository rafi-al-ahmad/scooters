<?php

namespace App\Listeners;

use App\Events\ApproveAppointmentEvent;
use App\Events\NewAppointmentEvent;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentNotification;
use App\Notifications\ApprovedAppointmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApprovedAppointmentNotification
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
     * @param  \App\Events\ApproveAppointmentEvent  $event
     * @return void
     */
    public function handle(ApproveAppointmentEvent $event)
    {
        $appointment = Appointment::find($event->appointment->id);
        $appointment->user->notify(new ApprovedAppointmentNotification($appointment));
    }
}
