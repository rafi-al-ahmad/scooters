<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{

    protected function validationRules()
    {
        return [
            'title' => ['required', 'max:190'],
            'code' => ['required', 'string'], //, 'unique:products,code'
            'description' => ['required', 'string'],
            'meta_desc' => ['required', 'string'],
            'warranty' => ['required', 'numeric'],
            'status' => ['required', 'in:1,2'],
            'brand_id' => ['required', 'exists:brands,id'],
            'collection_id' => ['required', 'exists:collections,id'],
            'technical_specifications' => ['nullable', 'array'],
            'product_type' => ['required', 'in:scooter,part,accessory'],
            'part_type' => ['nullable', 'exists:parts,id'],
            'language' => ['required', 'string', 'max:6'],
            'main_image' => ['required', 'imageable'],
            'images' => ['nullable', 'array'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['string'],
            'images.*' => ['imageable'],
            'variants' => ['nullable', 'array'],
            'variants.*' => ['array'],
            'variants.*.title' => ['required', 'string'],
            'variants.*.type' => ['nullable', 'string'],
            'variants.*.values' => ['required', 'array'],
            'variants.*.values.*' => ['string'],
            'combinations' => ['exclude_if:variants,null', 'nullable', 'array'],
            'combinations.*' => ['array'],
            'combinations.*.variants' => [
                'exclude_if:variants,null',
                'array',
                function ($attribute, $combinationVariant, $fail) {
                    $variants = request()->get('variants'); // Retrieve variants from variants attribute
                    $variantsTitles = [];
                    $variantsValues = [];
                    if (isset($variants)) {

                        // get all product variants and store them in array
                        foreach ($variants as $variant) {
                            $variantsTitles[] = $variant['title'];
                            $variantsValues[$variant['title']] = $variant['values'];
                        }

                        foreach ($combinationVariant as $combinationVariantKey => $combinationVariantValue) {

                            // check if combination variant is one of product variants,
                            // and if its one or product variant remove it from product variants array
                            if (in_array($combinationVariantKey, $variantsTitles)) {
                                unset($variantsTitles[array_search($combinationVariantKey, $variantsTitles)]);
                            }

                            //check if the combanition variant value is one of the product variant values
                            if (!in_array($combinationVariantValue, $variantsValues[$combinationVariantKey])) {
                                $fail('combinations variant value must be one of product variants value.');
                            }
                        }

                        // if product variants array not empty thats mean there missing variants in combination
                        if (count($variantsTitles) > 0) {
                            return $fail('All variant must be set in combinations.');
                        }
                    }
                },
            ],
            'combinations.*.sku' => ['nullable', 'string'],
            'combinations.*.quantity' => ['required', 'numeric'],
            'combinations.*.price' => ['required', 'numeric'],
            'combinations.*.discount' => ['nullable', 'numeric', 'max:1', 'min:0'],
            'combinations.*.image' => ['nullable', 'imageable'],
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return ProductResource::collection(Product::paginate($request->limit));
    }

    /**
     * Get all active products with their chiledren
     *
     * @return App\Http\Resources\CollectionResource
     */
    public function allActive(Request $request)
    {
        $products = Product::where('status', 1)->paginate($request->limit);
        return ProductResource::collection($products);
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

        //create product Eloquent object
        $product = new Product();
        $product->setTranslation('title', $data['language'], $data['title']);
        $product->setTranslation('description', $data['language'], $data['description']);
        $product->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $product->code = $data['code'];
        $product->warranty = $data['warranty'];
        $product->status = $data['status'];
        $product->brand_id = $data['brand_id'];
        $product->collection_id = $data['collection_id'];
        $product->technical_specifications = isset($data['technical_specifications']) ? $data['technical_specifications'] : [];
        $product->product_type = $data['product_type'];
        $product->languages = [$data['language']];
        $product->videos = $data['videos'];

        if (isset($data['part_type'])) {
            $product->options = ['part_type' => $data['part_type']];
        }

        //save product to database
        $product->save();

        // create product main image
        $this->addMediaFromBased64(
            model: $product,
            based64String: $data['main_image'],
            withResponsiveImages: true,
            properties: ["is_default" => 1]
        );

        // create product images
        foreach ($data['images'] as  $base64image) {
            $this->addMediaFromBased64(
                model: $product,
                based64String: $base64image,
                withResponsiveImages: true,
            );
        }



        // work with variants
        if (isset($data['variants'])) {
            foreach ($data['variants'] as  $variant) {

                // create variant
                $createdVariant = new Variant();
                $createdVariant->setTranslation('title', $data['language'],  $variant['title']);
                $createdVariant->type = $variant['type'];
                $createdVariant->product_id = $product->id;
                $createdVariant->save();

                // create variant values
                foreach ($variant['values'] as  $variantValue) {
                    $variantValues = new VariantValue();
                    $variantValues->setTranslation('value', $data['language'],  $variantValue);
                    $variantValues->variant_id = $createdVariant->id;
                    $variantValues->save();
                }
            }

            // create combinations
            foreach ($data['combinations'] as $combination) {
                if (!isset($combination["sku"])) {
                    $variantSKU = $product->code;
                    foreach ($combination['variants'] as  $combinationVariant) {
                        $variantSKU .= '-' . $combinationVariant;
                    }
                } else {
                    $variantSKU = $combination["sku"];
                }

                $productCombination = ProductCombination::create([
                    'sku' => $variantSKU,
                    'product_id' => $product->id,
                    'variants' => json_encode($combination["variants"]),
                    'price' => $combination["price"],
                    'discount_percent' => isset($combination["discount"]) ? $combination["discount"] : 0,
                    'quantity' => $combination["quantity"],
                ]);

                if (isset($combination["image"])) {
                    $this->addMediaFromBased64(
                        model: $productCombination,
                        based64String: $base64image,
                        withResponsiveImages: true,
                    );
                }
            }
        }


        return response()->json([
            'product' => $product,
        ]);
    }


    public function addMediaFromBased64($model, $based64String, $properties = [], $withResponsiveImages = false)
    {

        $imageExt = $this->getImageExtFromBase64($based64String);
        $imageName = time() . '.' . $imageExt;

        $media = $model->addMediaFromBase64($based64String);
        if ($properties) {
            $media = $media->withCustomProperties($properties);
        }
        $media = $media->usingFileName($imageName);
        if ($withResponsiveImages) {
            $media = $media->withResponsiveImages();
        }
        $media = $media->toMediaCollection();
    }

    /**
     * get the image file extention from based64 string
     */
    public function getImageExtFromBase64($base64data)
    {
        if (str_contains($base64data, ';base64')) {
            [$_, $base64data] = explode(';', $base64data);
            [$_, $base64data] = explode(',', $base64data);
        }
        $imgdata = base64_decode($base64data);

        $f = finfo_open();

        $mime_type = finfo_buffer($f, $imgdata, FILEINFO_MIME_TYPE);

        return str_replace("image/", "", $mime_type);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $product_id
     * @return \Illuminate\Http\Response
     */
    public function show($product_id)
    {
        $product = Product::findOrFail($product_id);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'description' => $product->description,
                'meta_desc' => $product->meta_desc,
                'code' => $product->code,
                'warranty' => $product->warranty,
                'brand_id' => $product->brand_id,
                'collection_id' => $product->collection_id,
                'product_type' => $product->product_type,
                'languages' => $product->languages,
                'videos' => $product->videos,
                'status' => $product->status,
                'technical_specifications' => $product->technical_specifications,
                'main_image' => $product->getMainImage(),
                'media' => $product->getPreparedMedia(),
                'variants' => $product->variants,
                'combinations' => $product->combinations,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
