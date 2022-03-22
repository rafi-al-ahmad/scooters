<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $query = Appointment::query(); 
        if ($request->user) {
            $query->where('user_id', $request->user);
        }
        
        return AppointmentResource::collection($query->paginate($request->limit));
    }

    /**
     * Get all active products with their chiledren
     *
     * @return App\Http\Resources\CollectionResource
     */
    public function userAppointments(Request $request)
    {
        $user_id = $this->currentUserId();
        return AppointmentResource::collection(Appointment::where('user_id', $user_id)->paginate($request->limit));
    }


    protected function validationRules()
    {
        return [
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date', 'date_format:Y-m-d H:i'],
            'service' => ['required', 'exists:services,id,deleted_at,NULL'],
            'product' => ['required', 'exists:products,id,deleted_at,NULL'],
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules(), []))->validate();

        $appointment = Appointment::create([
            'description' => $data["description"],
            'date' => Carbon::create($data["date"]),
            'user_id' => $this->currentUserId(),
            'product_id' => $data["product"],
            'service_id' => $data["service"],
            'approved' => 0,
        ]);


        return response()->json([
            'appointment' => $appointment,
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int $appointment_id
     * @return \Illuminate\Http\Response
     */
    public function show($appointment_id)
    {
        $appointment = Appointment::findOrFail($appointment_id);

        return response()->json([
            'appointment' => $appointment
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules(), [
            'appointment_id' => ['required', 'exists:appointments,id,deleted_at,NULL'],
            'approved' => ['required', Rule::in([0, 1])]
        ]))->validate();

        $appointment = Appointment::find($data['appointment_id']);
        $appointment->update([
            'description' => $data["description"],
            'date' => Carbon::create($data["date"]),
            'user_id' => $this->currentUserId(),
            'service_id' => $data["service"],
            'product_id' => $data["product"],
            'approved' => $data["approved"],
        ]);


        return response()->json([
            'appointment' => $appointment,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $appointment_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($appointment_id)
    {
        $appointment = Appointment::findOrFail($appointment_id);
        $appointment->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
