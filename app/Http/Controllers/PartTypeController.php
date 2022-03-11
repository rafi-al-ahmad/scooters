<?php

namespace App\Http\Controllers;

use App\Http\Resources\PartTypeResource;
use App\Models\PartType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartTypeController extends Controller
{
    protected $validationRules = [
        'title' => ['required', 'max:60'],
        'language' => ['required', 'max:30'],
        'status' => ['required', 'in:1,2'],
    ];


    /**
     * Display a listing of all part_types.
     *
     * @return App\Http\Resources\PartTypeResource
     */
    public function index()
    {
        return PartTypeResource::collection(PartType::all());
    }

    /**
     * Get all active part types
     *
     * @return App\Http\Resources\PartTypeResource
     */
    public function allActive()
    {
        $partTypes = PartType::where('status', 1)->get();
        return PartTypeResource::collection($partTypes);
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

        $partType = new PartType();
        $partType->setTranslation('title', $data['language'], $data['title']);
        $partType->status = $data['status'];
        $partType->languages = [$data['language']];
        $partType->save();

        return  response()->json([
            'part_type' => $partType
        ]);
    }

    /**
     * get the specified resource.
     *
     * @param  int $partTypeId
     * @return \Illuminate\Http\Response
     */
    public function show($partTypeId)
    {
        $partType = PartType::findOrFail($partTypeId);

        return  response()->json([
            'partType' => $partType
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $partTypeId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $partTypeId)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules, []))->validate();

        $partType = PartType::findOrFail($partTypeId);

        $partType->setTranslation('title', $data['language'], $data['title']);
        $partType->status = $data['status'];

        if (!in_array($data['language'], $partType->languages)) {
            $partType->languages = array_merge([$data['language']], $partType->languages);
        }

        $partType->save();

        $partType->setDisplyLanguage($data['language']);

        return  response()->json([
            'part_type' => $partType
        ]);
    }

    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int $partTypeId
     * @return \Illuminate\Http\Response
     */
    public function destroy($partTypeId)
    {
        $partType = PartType::findOrFail($partTypeId);
        $partType->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Remove the specified translation from the resource.
     *
     * @param  int $partTypeId
     * @return \Illuminate\Http\Response
     */
    public function deleteTranslation($partTypeId, $language)
    {

        $partType = PartType::findOrFail($partTypeId);
        
        if (!in_array($language, $partType->languages)) {
            return response()->json([
                'part_type' => $partType
            ]);
        }

        $partType->forgetTranslation('title', $language);
        $languages = $partType->languages;
        unset($languages[array_search($language, $languages)]);
        $partType->languages = $languages;
        $partType->save();

        return  response()->json([
            'part_type' => $partType
        ]);
    }
}
