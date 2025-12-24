<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

// Intervention Image v3
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $items = Item::where('user_id', Auth::id())->orderBy('created_at', 'desc')->paginate(10);
        return view('items.index', compact('items'));
    }

    public function userItems(User $user)
    {
        // Any authenticated user can view another user's items page
        $currentUser = Auth::user();

        // Add friendship status to the user
        $is_friend = $currentUser->following()->where('friend_id', $user->id)->exists();
        $user->is_friend = $is_friend;

        $items = Item::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate(10);
        return view('items.user_items', compact('items', 'user'));
    }

    public function usersIndex()
    {
        // Only admins can access the users list
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            abort(403, 'Доступ разрешен только администратору.');
        }

        $users = \App\Models\User::all();
        return view('users.index', compact('users'));
    }

    public function browseUsers()
    {
        // Regular users can browse all users (except themselves)
        $currentUser = Auth::user();
        $users = \App\Models\User::where('id', '!=', $currentUser->id)->get();

        // Add friendship status to each user
        foreach ($users as $user) {
            $user->is_friend = $currentUser->following()->where('friend_id', $user->id)->exists();
        }

        return view('users.browse', compact('users'));
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

        $data['user_id'] = Auth::id();
        Item::create($data);

        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function show(Item $item)
    {
        // Any authenticated user can view any item
        $user = Auth::user();

        // Check if the current user is friend of the item owner
        $is_friend = false;
        if ($user) {
            $is_friend = $user->following()->where('friend_id', $item->user_id)->exists();
        }

        $item->is_friend = $is_friend;

        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        // Check if the user can edit this item (owner or admin)
        $user = Auth::user();
        if ($item->user_id !== $user->id && !$user->is_admin) {
            abort(403);
        }

        return view('items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        // Check if the user can update this item (owner or admin)
        $user = Auth::user();
        if ($item->user_id !== $user->id && !$user->is_admin) {
            abort(403);
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

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        // Check if the user can delete this item (owner)
        $user = Auth::user();
        if ($item->user_id !== $user->id && !$user->is_admin) {
            abort(403);
        }

        if ($item->user_id === $user->id) {
            // Regular users can only soft delete their own items
            // Don't delete the image file during soft delete in case the item is restored
            $item->delete(); // This will soft delete due to SoftDeletes trait

            return redirect()->route('items.index')->with('success', 'Item soft deleted successfully.');
        } elseif ($user->is_admin) {
            // Admins have additional options - for this implementation,
            // they can permanently delete if needed (via a different route)
            // Don't delete the image file during soft delete in case the item is restored
            $item->delete(); // This will soft delete due to SoftDeletes trait

            return redirect()->route('items.index')->with('success', 'Item soft deleted successfully.');
        }

        abort(403);
    }

    public function forceDestroy($id)
    {
        // Only admins can permanently delete items
        $user = Auth::user();
        if (!$user->is_admin) {
            abort(403);
        }

        $item = Item::withTrashed()->findOrFail($id);

        if ($item->image && Storage::exists('public/' . $item->image)) {
            Storage::delete('public/' . $item->image);
        }

        $item->forceDelete();

        return redirect()->route('items.index')->with('success', 'Item permanently deleted.');
    }

    public function restore($id)
    {
        // Only admins can restore soft deleted items
        $item = Item::withTrashed()->findOrFail($id);

        $user = Auth::user();
        if (!$user->is_admin) {
            abort(403);
        }

        $item->restore();

        return redirect()->route('items.index')->with('success', 'Item restored successfully.');
    }

    public function trashed()
    {
        // Debug: Show current user info
        $user = Auth::user();

        \Log::info('Trashed method accessed', [
            'user_exists' => $user ? true : false,
            'user_id' => $user ? $user->id : null,
            'is_admin' => $user ? ($user->is_admin ? 'yes' : 'no') : 'N/A',
            'is_admin_raw' => $user ? $user->getOriginal('is_admin') : 'N/A'
        ]);

        // Show soft deleted items - only for admins
        if (!Auth::check()) {
            \Log::info('Auth check failed');
            abort(403, 'Вы должны войти в систему.');
        }

        if (!$user || !$user->is_admin) {
            \Log::info('Admin check failed', [
                'user_exists' => $user ? true : false,
                'is_admin_value' => $user ? $user->is_admin : 'null'
            ]);
            abort(403, 'Доступ разрешен только администратору.');
        }

        $items = Item::onlyTrashed()
                     ->with('user') // Eager load the user relationship to avoid n+1 and potential issues
                     ->orderBy('deleted_at', 'desc')
                     ->paginate(10);

        return view('items.trashed', compact('items'));
    }

    /**
     * Display the user's feed with items from their friends.
     */
    public function feed()
    {
        $user = Auth::user();

        // Get the IDs of the user's friends
        $friendIds = $user->following()->pluck('users.id')->toArray();

        // Include current user's ID to show their own posts too
        $userIds = array_merge($friendIds, [$user->id]);

        // Get items from friends and the user, ordered by creation date
        $items = Item::whereIn('user_id', $userIds)
                     ->with('user', 'comments') // Eager load user and comments
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);

        return view('items.feed', compact('items', 'user'));
    }
}
