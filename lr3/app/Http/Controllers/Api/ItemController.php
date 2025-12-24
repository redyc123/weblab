<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $items = Item::with(['user', 'comments'])->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ItemResource::collection($items)
        ]);
    }

    /**
     * Display a listing of items for authenticated user.
     *
     * @return JsonResponse
     */
    public function myItems(): JsonResponse
    {
        $items = Item::where('user_id', Auth::id())
                     ->with(['user', 'comments'])
                     ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => ItemResource::collection($items)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:4096',
            'released_at' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = 'items/' . $filename;

            // ImageManager v3
            $manager = new ImageManager(new Driver());

            // read image
            $image = $manager->read($file->getRealPath());

            // resize
            $image = $image->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // encode and save
            Storage::put("public/$path", (string)$image->toJpeg());

            $data['image'] = $path;
        }

        $data['user_id'] = Auth::id();
        $item = Item::create($data);

        return response()->json([
            'success' => true,
            'data' => new ItemResource($item),
            'message' => 'Item created successfully.'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Item $item
     * @return JsonResponse
     */
    public function show(Item $item): JsonResponse
    {
        $item->load(['user', 'comments']);

        return response()->json([
            'success' => true,
            'data' => new ItemResource($item),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Item $item
     * @return JsonResponse
     */
    public function update(Request $request, Item $item): JsonResponse
    {
        $user = Auth::user();
        if ($item->user_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this item.'
            ], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:4096',
            'released_at' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            // delete old image
            if ($item->image && Storage::exists('public/' . $item->image)) {
                Storage::delete('public/' . $item->image);
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = 'items/' . $filename;

            // ImageManager v3
            $manager = new ImageManager(new Driver());

            $image = $manager->read($file->getRealPath());

            $image = $image->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            Storage::put("public/$path", (string)$image->toJpeg());

            $data['image'] = $path;
        }

        $item->update($data);

        return response()->json([
            'success' => true,
            'data' => new ItemResource($item),
            'message' => 'Item updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Item $item
     * @return JsonResponse
     */
    public function destroy(Item $item): JsonResponse
    {
        $user = Auth::user();
        if ($item->user_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this item.'
            ], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.'
        ]);
    }
}