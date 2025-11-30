<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Intervention Image v3
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::orderBy('created_at', 'desc')->paginate(10);
        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(Request $request)
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

        Item::create($data);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function show(Item $item)
    {
        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
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

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->image && Storage::exists('public/' . $item->image)) {
            Storage::delete('public/' . $item->image);
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted.');
    }
}
