<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    protected $validationRules = [
        'title' => ['required', 'max:60'],
        'status' => ['required', 'in:1,2'],
    ];

    /**
     * Display a listing of the Brands.
     *
     * @return App\Http\Resources\BrandResource
     */
    public function index()
    {
        return BrandResource::collection(Brand::all());
    }

    /**
     * Get all active brands
     *
     * @return App\Http\Resources\BrandResource
     */
    public function allActive()
    {
        $brands = Brand::where('status', 1)->get();
        return BrandResource::collection($brands);
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

        $brand = new Brand();
        $brand->title = $data['title'];
        $brand->status = $data['status'];
        $brand->save();

        return  response()->json([
            'brand' => $brand
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $brand_id
     * @return \Illuminate\Http\Response
     */
    public function show($brand_id)
    {
        $brand= Brand::findOrFail($brand_id);

        return  response()->json([
            'brand' => $brand
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $brand_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $brand_id)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules, []))->validate();

        $brand = Brand::find($brand_id);
        $brand->title = $data['title'];
        $brand->status = $data['status'];
        $brand->save();

        return  response()->json([
            'brand' => $brand
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $brand_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($brand_id)
    {
        $brand = Brand::findOrFail($brand_id);
        $brand->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
