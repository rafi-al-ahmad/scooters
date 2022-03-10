<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{

    protected $validationRules = [
        'title' => ['required', 'max:60'],
        'description' => ['required', 'max:4000'],
        'language' => ['required', 'max:30'],
        'status' => ['required', 'in:1,2'],
    ];


    /**
     * Display a listing of the all services.
     *
     * @return App\Http\Resources\ServiceResource
     */
    public function index()
    {
        return ServiceResource::collection(Service::all());
    }


    /**
     * Get all active services
     *
     * @return App\Http\Resources\ServiceResource
     */
    public function allActive()
    {
        $services = Service::where('status', 1)->get();
        return ServiceResource::collection($services);
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
        Validator::make($data, array_merge($this->validationRules, []))->validate();

        $service = new Service();
        $service->setTranslation('title', $data['language'], $data['title']);
        $service->setTranslation('description', $data['language'], $data['description']);
        $service->status = $data['status'];
        $service->languages = [$data['language']];
        $service->save();

        return  response()->json([
            'service' => $service
        ]);
    }

    /**
     * get the specified resource.
     *
     * @param  int $service_id
     * @return \Illuminate\Http\Response
     */
    public function show($service_id)
    {
        $service= Service::findOrFail($service_id);

        return  response()->json([
            'service' => $service
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $service_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $service_id)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules, []))->validate();

        $service = Service::findOrFail($service_id);

        $service->setTranslation('title', $data['language'], $data['title']);
        $service->setTranslation('description', $data['language'], $data['description']);
        $service->status = $data['status'];

        if (!in_array($data['language'], $service->languages)) {
            $service->languages = array_merge([$data['language']], $service->languages);
        }

        $service->save();

        $service->setDisplyLanguage($data['language']);

        return  response()->json([
            'service' => $service
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $service_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($service_id)
    {
        $service = Service::findOrFail($service_id);
        $service->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Remove the specified translation from the resource.
     *
     * @param  int $service_id
     * @return \Illuminate\Http\Response
     */
    public function deleteTranslation($service_id, $language)
    {

        $service = Service::findOrFail($service_id);
        if (!in_array($language, $service->languages)) {
            return response()->json([
                'service' => $service
            ]);
        }

        $service->forgetTranslation('title', $language);
        $service->forgetTranslation('description', $language);
        $languages = $service->languages;
        unset($languages[array_search($language, $languages)]);
        $service->languages = $languages;
        $service->save();

        return  response()->json([
            'service' => $service
        ]);
    }
}
