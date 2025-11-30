<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Image; // Intervention Image facade

class ItemController extends Controller
{
    public function index()
    {
        // пагинация, без удалённых (soft delete)
        $items = Item::orderBy('created_at', 'desc')->paginate(10);
        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(Request $request)
    {
        // валидация
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048', // макс 2MB
            'released_at' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        // обработка изображения (если есть)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // оригинал сохранять не обязательно; создаём адаптированный размер
            $image = Image::make($file)->orientate()->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // сохраняем в storage/app/public/items
            $path = 'items/' . $filename;
            Storage::put('public/' . $path, (string) $image->encode());
            $data['image'] = $path;
        }

        $item = Item::create($data);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function show(Item $item)
    {
        // show возвращает отдельное blade (детальная информация)
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
            'image' => 'nullable|image|max:2048',
            'released_at' => 'nullable|date',
            'category' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            // удаляем старый, если есть
            if ($item->image && Storage::exists('public/' . $item->image)) {
                Storage::delete('public/' . $item->image);
            }

            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $image = Image::make($file)->orientate()->resize(800, 600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $path = 'items/' . $filename;
            Storage::put('public/' . $path, (string) $image->encode());
            $data['image'] = $path;
        }

        $item->update($data);

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        // soft delete
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted.');
    }
}
