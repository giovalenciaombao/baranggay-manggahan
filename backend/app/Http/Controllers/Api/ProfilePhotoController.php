<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfilePhoto;
use Illuminate\Http\Request;

class ProfilePhotoController extends Controller
{
    public function show(Request $request)
    {
        $photo = ProfilePhoto::find($request->user()->id);

        if (!$photo) {
            return response()->noContent();
        }

        return response($photo->contents, 200, [
            'Content-Type' => $photo->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096',
        ]);

        $file = $request->file('photo');

        ProfilePhoto::upsert([[
            'user_id' => $request->user()->id,
            'mime_type' => $file->getMimeType(),
            'contents' => encrypt($file->get(), false),
        ]], ['user_id'], ['mime_type', 'contents']);

        return response()->noContent();
    }
}
