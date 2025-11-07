<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function show()
    {
        $payload = AboutContent::query()->latest()->first();

        if (!$payload) {
            return response()->json([
                'data' => null,
                'message' => 'About content is not configured yet.',
            ], 200);
        }

        return response()->json([
            'data' => [
                'title' => $payload->title,
                'paragraph_one' => $payload->paragraph_one,
                'paragraph_two' => $payload->paragraph_two,
                'paragraph_three' => $payload->paragraph_three,
                'image_url' => $payload->image_url,
            ],
        ]);
    }
}
