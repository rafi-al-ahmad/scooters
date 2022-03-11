<?php

namespace App\Http\Controllers;

use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollectionController extends Controller
{
    
    protected $validationRules = [
        'title' => ['required', 'max:60'],
        'language' => ['required', 'max:30'],
        'status' => ['required', 'in:1,2'],
        'parent' => ['nullable', 'exists:collections,id'],
    ];


    /**
     * Display a listing of all collections.
     *
     * @return App\Http\Resources\CollectionResource
     */
    public function index()
    {
        return CollectionResource::collection(Collection::all());
    }

    /**
     * Get all active collections with their chiledren
     *
     * @return App\Http\Resources\CollectionResource
     */
    public function allActive()
    {
        $collections = Collection::where('status', 1)->whereNull('parent')->get();
        return CollectionResource::collection($collections);
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

        $collection = new Collection();
        $collection->setTranslation('title', $data['language'], $data['title']);
        $collection->parent = $request->parent;
        $collection->status = $data['status'];
        $collection->languages = [$data['language']];
        $collection->save();

        return  response()->json([
            'collection' => $collection
        ]);
    }

    /**
     * get the specified resource.
     *
     * @param  int $collection_id
     * @return \Illuminate\Http\Response
     */
    public function show($collection_id)
    {
        $collection = Collection::findOrFail($collection_id);

        return  response()->json([
            'collection' => $collection
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $collection_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $collection_id)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules, []))->validate();

        $collection = Collection::findOrFail($collection_id);

        $collection->setTranslation('title', $data['language'], $data['title']);
        $collection->status = $data['status'];
        $collection->parent = $request->parent;

        if (!in_array($data['language'], $collection->languages)) {
            $collection->languages = array_merge([$data['language']], $collection->languages);
        }

        $collection->save();

        $collection->setDisplyLanguage($data['language']);

        return  response()->json([
            'collection' => $collection
        ]);
    }

    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int $collection_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($collection_id)
    {
        $collection = Collection::findOrFail($collection_id);
        $collection->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Remove the specified translation from the resource.
     *
     * @param  int $collection_id
     * @return \Illuminate\Http\Response
     */
    public function deleteTranslation($collection_id, $language)
    {

        $collection = Collection::findOrFail($collection_id);
        if (!in_array($language, $collection->languages)) {
            return response()->json([
                'collection' => $collection
            ]);
        }

        $collection->forgetTranslation('title', $language);
        $languages = $collection->languages;
        unset($languages[array_search($language, $languages)]);
        $collection->languages = $languages;
        $collection->save();

        return  response()->json([
            'collection' => $collection
        ]);
    }
}
