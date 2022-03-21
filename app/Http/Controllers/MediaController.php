<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    //
    
    public function deleteMedia($media_id)
    {
        $media = Media::findOrFail($media_id);
        $media->delete();
        
        return response()->json([
            'success' => true
        ]);
    }

    public static function addMediaFromBased64($model, $based64String, $properties = [], $withResponsiveImages = false)
    {

        $imageExt = static::getImageExtFromBase64($based64String);
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
    public static function getImageExtFromBase64($base64data)
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
}
