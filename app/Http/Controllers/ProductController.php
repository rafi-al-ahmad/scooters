<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Feature;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Contracts\Service\Attribute\Required;

class ProductController extends Controller
{

    protected function validationRules()
    {
        return [
            'title' => ['required', 'max:190'],
            'code' => ['nullable', 'string',],
            'description' => ['required', 'string'],
            'meta_desc' => ['required', 'string'],
            'warranty' => ['required', 'numeric'],
            'status' => ['required', 'in:1,2'],
            'brand_id' => ['required', 'exists:brands,id'],
            'collection_id' => ['required', 'exists:collections,id'],
            'technical_specifications' => ['nullable', 'array'],
            'product_type' => ['required', 'in:scooter,part,accessory'],
            'part_type' => ['nullable', 'exists:parts,id'],
            'language' => ['required', 'string', Rule::in(config('app.supported_locales'))],
            'main_image' => ['required', 'imageable'],
            'videos' => ['nullable', 'array'],
            'videos.*' => ['string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['imageable'],
            'options' => ['nullable', 'array'],
            'options.*' => ['string', 'max:60'],
            'variants' => ['array', 'required'],
            'variants.*' => ['array'],
            'variants.*.sku' => ['nullable', 'string'],
            'variants.*.quantity' => ['required', 'numeric'],
            'variants.*.price' => ['required', 'numeric'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric'],
            'variants.*.image' => ['nullable', 'imageable'],
            'variants.*.options' => [
                'array',
                function ($attribute, $variantOptions, $fail) {
                    $options = request()->get('options'); // Retrieve options from options attribute
                    $optionsTitles = [];
                    if (isset($options)) {

                        // get all product options and store them in array
                        foreach ($options as $option) {
                            $optionsTitles[] = $option;
                        }

                        foreach ($variantOptions as $variantOptionKey => $variantOptionValue) {
                            // check if variant option is one of product options,
                            if (in_array($variantOptionKey, $optionsTitles)) {
                                unset($optionsTitles[array_search($variantOptionKey, $optionsTitles)]);
                            }
                        }

                        // if product options array not empty thats mean there missing options in variant
                        if (count($optionsTitles) > 0) {
                            return $fail('All options must be set in variant.');
                        }
                    }
                },
            ],

            'features' => ['array', 'nullable'],
            'features.*' => ['array'],
            'features.*.title' => ['required', 'string', 'max:80'],
            'features.*.image' => ['required', 'imageable'],
            'features.*.description' => ['nullable', 'string'],
            // 'combinations' => ['exclude_if:variants,null', 'nullable', 'array'],
            // 'combinations.*' => ['array'],
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
    public function activeWithFilters(Request $request)
    {
        $query = Product::where('status', 1); 
        if ($request->brand) {
            $query->where('brand_id', $request->brand);
        }
        
        if ($request->collection) {
            $query->where('collection_id', $request->collection);
        }
        
        if ($request->type) {
            $query->where('product_type', $request->type);
            
            if ($request->type == "part" && $request->part_type) {
                $query->where('options->part_type', $request->part_type);
            }
        }
        
        
        if ($request->key) {
            $key = $request->key;
            $query->where(function($query) use ($key) {
                $query->orWhere('title', 'like', '%'.$key.'%')
                ->orWhere('description', 'like', '%'.$key.'%')
                ->orWhere('meta_desc', 'like', '%'.$key.'%');
            });
        }


        return ProductResource::collection($query->paginate($request->limit));
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

        $product = new Product();
        $product->setTranslation('title', $data['language'], $data['title']);
        $product->setTranslation('description', $data['language'], $data['description']);
        $product->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $product->code = $data['code'];
        $product->warranty = $data['warranty'];
        $product->status = $data['status'];
        $product->brand_id = $data['brand_id'];
        $product->collection_id = $data['collection_id'];
        $product->technical_specifications = [$data['language'] => isset($data['technical_specifications']) ? $data['technical_specifications'] : []];
        $product->product_type = $data['product_type'];
        $product->languages = [$data['language']];
        $product->videos = $data['videos'];
        $product->variant_options = [$data['language'] => $data['options']];

        if (isset($data['part_type'])) {
            $product->options = ['part_type' => $data['part_type']];
        }

        //save product to database
        $product->save();

        // create product main image
        MediaController::addMediaFromBased64(
            model: $product,
            based64String: $data['main_image'],
            withResponsiveImages: true,
            properties: ["is_default" => 1]
        );

        // create product images
        foreach ($data['images'] as  $base64image) {
            MediaController::addMediaFromBased64(
                model: $product,
                based64String: $base64image,
                withResponsiveImages: true,
            );
        }

        // create product features
        if (isset($data['features'])) {
            foreach ($data['features'] as  $featureData) {
                $feature = new Feature();
                $feature->setTranslation('title', $data['language'], $featureData['title']);
                $feature->setTranslation('description', $data['language'], $featureData['description'] ?? "");
                $feature->product_id = $product->id;
                $feature->save();


                MediaController::addMediaFromBased64(
                    model: $feature,
                    based64String: $featureData["image"],
                    withResponsiveImages: true,
                );
            }
        }



        // work with variants and options
        if (isset($data['options'])) {
            foreach ($data['variants'] as  $variant) {

                if (!isset($variant["sku"])) {
                    $variantSKU = $product->code;
                    foreach ($variant['options'] as  $variantOption) {
                        $variantSKU .= '-' . $variantOption;
                    }
                } else {
                    $variantSKU = $variant["sku"];
                }

                $productVariant = Variant::create([
                    'sku' => $variantSKU,
                    'product_id' => $product->id,
                    'options' => [$data['language'] => $variant["options"]],
                    'price' => $variant["price"],
                    'compareAtPrice' => isset($variant["compareAtPrice"]) ? $variant["compareAtPrice"] : null,
                    'quantity' => $variant["quantity"],
                ]);

                if (isset($variant["image"])) {
                    MediaController::addMediaFromBased64(
                        model: $productVariant,
                        based64String: $variant['image'],
                        withResponsiveImages: true,
                    );
                }
            }
        }


        return response()->json([
            'product' => $product,
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int $product_id
     * @return \Illuminate\Http\Response
     */
    public function show($product_id)
    {
        return response()->json([
            'product' => $this->product($product_id)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->wantJson();

        $data = $request->all();
        Validator::make($data, array_merge($this->validationRules(), [
            'id' => ['required', 'exists:products,id'],
            'variants.*.id' => ['nullable', 'exists:variants,id'],
            'variants_to_delete' => ['nullable', 'array'],
            'variants_to_delete.*' => ['numeric', 'exists:variants,id'],
            'main_image' => ['nullable', 'imageable'],
            'features.*.id' => ['nullable', 'numeric', 'exists:features,id'],
        ]))->validate();


        $product = Product::find($data['id']);

        $product->setTranslation('title', $data['language'], $data['title']);
        $product->setTranslation('description', $data['language'], $data['description']);
        $product->setTranslation('meta_desc', $data['language'], $data['meta_desc']);
        $product->warranty = $data['warranty'];
        $product->status = $data['status'];
        $product->brand_id = $data['brand_id'];
        $product->collection_id = $data['collection_id'];
        $product->setTranslation(
            'technical_specifications',
            $data['language'],
            isset($data['technical_specifications']) ? $data['technical_specifications'] : []
        );
        $product->product_type = $data['product_type'];
        $product->videos = $data['videos'];
        $product->setTranslation('variant_options', $data['language'], $data['options']);


        if (!in_array($data['language'], $product->languages)) {
            $product->languages = array_merge([$data['language']], $product->languages);
        }

        if (isset($data['part_type'])) {
            $productOptions = $product->options;
            $productOptions['part_type'] = $data['part_type'];
            $product->options = $productOptions;
        }

        $product->save();

        if (isset($data['main_image'])) {

            //remove old main image
            $oldMainImage = $product->getMedia(filters: ['is_default' => 1])->first();
            $oldMainImage->forgetCustomProperty('is_default');

            // create new main image
            MediaController::addMediaFromBased64(
                model: $product,
                based64String: $data['main_image'],
                withResponsiveImages: true,
                properties: ["is_default" => 1]
            );
        }

        // create product new images
        foreach ($data['images'] as  $base64image) {
            MediaController::addMediaFromBased64(
                model: $product,
                based64String: $base64image,
                withResponsiveImages: true,
            );
        }


        //delete canceled features
        Feature::whereIn('id', $data['features_to_delete'] ?? [])->get()->each->delete();

        // update or create product features
        if (isset($data['features'])) {
            foreach ($data['features'] as  $featureData) {

                if (isset($featureData["id"])) {
                    $feature = Feature::find($featureData["id"]);
                    $feature->setTranslation('title', $data['language'], $featureData['title']);
                    $feature->setTranslation('description', $data['language'], $featureData['description'] ?? "");
                    $feature->product_id = $product->id;
                    $feature->save();

                    if (isset($featureData['image'])) {
                        $feature->getFirstMedia()?->delete();

                        MediaController::addMediaFromBased64(
                            model: $feature,
                            based64String: $featureData["image"],
                            withResponsiveImages: true,
                        );
                    }
                } else {
                    $feature = new Feature();
                    $feature->setTranslation('title', $data['language'], $featureData['title']);
                    $feature->setTranslation('description', $data['language'], $featureData['description'] ?? "");
                    $feature->product_id = $product->id;
                    $feature->save();

                    MediaController::addMediaFromBased64(
                        model: $feature,
                        based64String: $featureData["image"],
                        withResponsiveImages: true,
                    );
                }
            }
        }

        //delete canceled variants
        Variant::whereIn('id', $data['variants_to_delete'] ?? [])->get()->each->delete();

        // update variants and options
        foreach ($data['variants'] as  $variant) {

            if (!isset($variant["sku"])) {
                $variantSKU = $product->code;
                foreach ($variant['options'] as  $variantOption) {
                    $variantSKU .= '-' . $variantOption;
                }
            } else {
                $variantSKU = $variant["sku"];
            }

            if (isset($variant["id"])) {
                $productVariant = Variant::find($variant["id"]);
                $productVariant->sku = $variantSKU;
                $productVariant->setTranslation('options', $data['language'], $variant['options']);
                $productVariant->price = $variant['price'];
                $productVariant->compareAtPrice = $variant['compareAtPrice'] ?? null;
                $productVariant->quantity = $variant['quantity'];
                $productVariant->save();
            } else {
                $productVariant = Variant::create([
                    'sku' => $variantSKU,
                    'product_id' => $product->id,
                    'options' => [$data['language'] => $variant["options"]],
                    'price' => $variant["price"],
                    'compareAtPrice' => $variant["compareAtPrice"] ?? null,
                    'quantity' => $variant["quantity"],
                ]);
            }

            if (isset($variant["image"])) {
                MediaController::addMediaFromBased64(
                    model: $productVariant,
                    based64String: $variant['image'],
                    withResponsiveImages: true,
                );
            }
        }


        return response()->json([
            'product' => $this->product($product->id)
        ]);
    }

    // return product data
    public function product($product_id)
    {
        $product = Product::findOrFail($product_id);

        
        $productData = [
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
            'options' => $product->variant_options,
            'variants' => $product->variants,
            'features' => $product->features,
            'media' => $product->getPreparedMedia(),
            'main_image' => $product->getMainImage(),
        ];
        
        if ($product->product_type == "part" && isset($product->options['part_type'])) {
            $productData['part_type'] = $product->options['part_type'];
        }

        return $productData;
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $product_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($product_id)
    {
        $product = Product::findOrFail($product_id);
        $product->delete();

        return response()->json([
            'success' => true
        ]);
    }


}
