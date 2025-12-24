# Лабораторная работа №5 - Отчет

## Тема: Работа с реляционной БД

### Выполненные изменения:

## 1. Создание вспомогательной таблицы "comments"
### 1.1. Создание миграции для таблицы комментариев
- **Файл**: `database/migrations/2025_12_23_103232_create_comments_table.php`
- **Изменения**:
  - Создание таблицы `comments` с полями: `content`, `user_id`, `item_id`
  - Создание внешних ключей к таблицам `users` и `items`
  - Добавление каскадного удаления связанных записей
- **Код**:
```php
public function up()
{
    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->text('content');
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('item_id');
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
    });
}
```

## 2. Создание вспомогательной таблицы "friendships"
### 2.1. Создание миграции для таблицы дружб
- **Файл**: `database/migrations/2025_12_23_103238_create_friendships_table.php`
- **Изменения**:
  - Создание таблицы `friendships` с полями: `user_id`, `friend_id`, `accepted`, `timestamps`
  - Создание внешних ключей к таблице `users` для обеих сторон дружбы
  - Добавление уникального индекса на пару `user_id`, `friend_id`
- **Код**:
```php
public function up()
{
    Schema::create('friendships', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');        // Кто добавляет в друзья
        $table->unsignedBigInteger('friend_id');      // Кого добавляют в друзья
        $table->boolean('accepted')->default(false);  // Для двусторонней дружбы, если нужно
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('friend_id')->references('id')->on('users')->onDelete('cascade');

        // Предотвращение дубликатов дружб
        $table->unique(['user_id', 'friend_id']);
    });
}
```

## 3. Создание модели Comment
### 3.1. Определение модели Comment
- **Файл**: `app/Models/Comment.php`
- **Изменения**:
  - Создание модели Comment с полями `content`, `user_id`, `item_id`
  - Добавление отношений к пользователям и элементам
- **Код**:
```php
class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'user_id',
        'item_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Item::class);
    }
}
```

## 4. Изменения в модели User
### 4.1. Добавление отношений к комментариям и дружбам
- **Файл**: `app/Models/User.php`
- **Изменения**:
  - Добавление отношений `comments()`, `items()`, `following()`, `followers()`
- **Код**:
```php
/**
 * Получить комментарии пользователя.
 */
public function comments(): HasMany
{
    return $this->hasMany(\App\Models\Comment::class);
}

/**
 * Получить пользователей, на которых подписан этот пользователь (друзья).
 */
public function following(): BelongsToMany
{
    return $this->belongsToMany(\App\Models\User::class, 'friendships', 'user_id', 'friend_id')
                ->withTimestamps();
}

/**
 * Получить пользователей, которые подписаны на этого пользователя (подписчики).
 */
public function followers(): BelongsToMany
{
    return $this->belongsToMany(\App\Models\User::class, 'friendships', 'friend_id', 'user_id')
                ->withTimestamps();
}
```

## 5. Изменения в модели Item
### 5.1. Добавление отношения к комментариям
- **Файл**: `app/Models/Item.php`
- **Изменения**:
  - Добавление отношения `comments()` к модели Item
- **Код**:
```php
/**
 * Получить комментарии к элементу.
 */
public function comments(): HasMany
{
    return $this->hasMany(\App\Models\Comment::class);
}
```

## 6. Создание контроллера CommentController
### 6.1. Реализация методов для управления комментариями
- **Файл**: `app/Http/Controllers/CommentController.php`
- **Изменения**:
  - Метод `store()` для создания комментариев
  - Метод `destroy()` для удаления комментариев
  - Метод `toggleFriendship()` для управления дружбами
- **Код**:
```php
public function store(Request $request)
{
    $request->validate([
        'content' => 'required|string|max:500',
        'item_id' => 'required|exists:items,id',
    ]);

    $comment = new Comment();
    $comment->content = $request->content;
    $comment->user_id = Auth::id();
    $comment->item_id = $request->item_id;
    $comment->save();

    return redirect()->back()->with('success', 'Комментарий успешно добавлен.');
}

public function toggleFriendship($userId)
{
    $user = Auth::user();
    $friend = \App\Models\User::findOrFail($userId);

    if ($user->id == $friend->id) {
        return redirect()->back()->with('error', 'Вы не можете добавить себя в друзья.');
    }

    // Проверить, существует ли уже дружба
    $existingFriendship = \DB::table('friendships')
        ->where([
            ['user_id', $user->id],
            ['friend_id', $friend->id]
        ])
        ->first();

    if ($existingFriendship) {
        // Удалить дружбу
        \DB::table('friendships')
            ->where([
                ['user_id', $user->id],
                ['friend_id', $friend->id]
            ])
            ->delete();

        return redirect()->back()->with('success', 'Друг успешно удален.');
    } else {
        // Создать связь дружбы
        \DB::table('friendships')->insert([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Также создать обратную связь дружбы (оба пользователя становятся друзьями друг друга)
        \DB::table('friendships')->insert([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Друг успешно добавлен.');
    }
}
```

## 7. Изменения в ItemController
### 7.1. Добавление метода для ленты пользователя
- **Файл**: `app/Http/Controllers/ItemController.php`
- **Изменения**:
  - Добавление метода `feed()` для отображения ленты друзей
- **Код**:
```php
public function feed()
{
    $user = Auth::user();

    // Получить ID друзей пользователя
    $friendIds = $user->following()->pluck('users.id')->toArray();

    // Включить ID текущего пользователя, чтобы показывать и свои посты тоже
    $userIds = array_merge($friendIds, [$user->id]);

    // Получить элементы от друзей и пользователя, отсортированные по дате создания
    $items = Item::whereIn('user_id', $userIds)
                 ->with('user', 'comments') // Загрузить пользователя и комментарии
                 ->orderBy('created_at', 'desc')
                 ->paginate(10);

    return view('items.feed', compact('items', 'user'));
}
```

## 8. Маршруты
### 8.1. Добавление новых маршрутов
- **Файл**: `routes/web.php`
- **Изменения**:
  - Добавление маршрутов для комментариев, дружб и ленты
- **Код**:
```php
// Маршрут ленты для элементов друзей пользователя
Route::get('/feed', [ItemController::class, 'feed'])->name('items.feed');

// Маршруты комментариев
Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

// Маршруты дружбы
Route::post('/users/{user}/toggle-friendship', [CommentController::class, 'toggleFriendship'])->name('users.toggle-friendship');
```

## 9. Представления (Views)
### 9.1. Создание страницы ленты
- **Файл**: `resources/views/items/feed.blade.php`
- **Изменения**:
  - Создание страницы ленты с элементами друзей
  - Отображение комментариев для каждого элемента
  - Блок друзей с возможностью добавления/удаления

### 9.2. Изменения в странице просмотра элемента
- **Файл**: `resources/views/items/show.blade.php`
- **Изменения**:
  - Добавление раздела комментариев
  - Возможность добавлять и удалять комментарии
  - Подсветка комментариев от друзей

### 9.3. Изменения в главной странице
- **Файл**: `resources/views/items/index.blade.php`
- **Изменения**:
  - Добавление кнопки перехода к ленте

### 9.4. Изменения в странице пользователей
- **Файл**: `resources/views/users/index.blade.php`
- **Изменения**:
  - Добавление статуса дружбы для каждого пользователя
  - Кнопки добавления/удаления из друзей

## 10. Изменения в навигации
### 10.1. Добавление ссылки на ленту
- **Файл**: `resources/views/layouts/app.blade.php`
- **Изменения**:
  - Добавление кнопки "Feed" в навигационную панель

## 11. Функциональность реализованных критериев

### Базовый уровень:
1. ✅ **Пользователи могут добавлять связанные сущности (комментарии) к собственным и чужим объектам данных** - Реализована форма добавления комментариев к любым элементам
2. ✅ **Пользователи могут "дружить", добавляя и удаляя связи друг с другом** - Реализована система дружб с кнопками добавления/удаления

### Расширенный уровень:
1. ✅ **Критерии базового уровня** - Все реализованы
2. ✅ **Реализована лента пользователя, в которую попадают новые объекты в профиле "друзей" в хронологическом порядке** - Создана страница `/feed` с элементами от друзей, отсортированными по дате
3. ✅ **Реализована подсветка комментариев (цветом или иконкой) от "друзей" при просмотре авторизованным пользователем любого объекта** - Видно на странице просмотра элемента и на странице ленты
4. ✅ **Реализовано событие "обоюдной дружбы". Как только один из пользователей добавляет второго в друзья автоматически создается обратная связь** - При добавлении дружбы создаются обе записи в таблице

## Вывод
Все требования базового и расширенного уровней выполнены. Создана система комментариев, позволяющая пользователям оставлять комментарии к элементам других пользователей. Реализована система дружб, позволяющая пользователям взаимодействовать друг с другом. Лента пользователя показывает элементы от друзей в хронологическом порядке, а комментарии от друзей подсвечиваются. Система дружб реализована по принципу обоюдной дружбы (когда один пользователь добавляет другого, создается обратная связь автоматически).

## Обновления (Реализация доступа обычных пользователей)

### 1. Добавлена возможность обычным пользователям просматривать других пользователей
- **Файл**: `routes/web.php`
- **Изменения**:
  - Добавлен маршрут `GET /users/browse` для просмотра списка всех пользователей
  - Создан метод `browseUsers()` в `ItemController`
- **Код**:
```php
Route::get('/users/browse', [ItemController::class, 'browseUsers'])->name('users.browse');
```

### 2. Реализованы веб-страницы для просмотра пользователей
- **Файлы**:
  - `app/Http/Controllers/ItemController.php` - метод `browseUsers()` и обновленный метод `userItems()`
  - `resources/views/users/browse.blade.php` - страница просмотра пользователей
  - `resources/views/items/user_items.blade.php` - страница просмотра объектов другого пользователя
- **Изменения**:
  - Метод `browseUsers()` позволяет обычным пользователям просматривать других пользователей (кроме себя)
  - Метод `userItems()` позволяет просматривать страницу любого пользователя
  - Обновлены шаблоны для отображения статуса дружбы и кнопок добавления в друзья
- **Код**:
```php
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
```

### 3. Создан контроллер для управления дружбой
- **Файл**: `app/Http/Controllers/UserController.php`
- **Изменения**:
  - Создан метод `toggleFriendship()` для добавления/удаления друзей
- **Код**:
```php
public function toggleFriendship($userId)
{
    $user = Auth::user();
    $friend = User::findOrFail($userId);

    if ($user->id == $friend->id) {
        return redirect()->back()->with('error', 'Вы не можете добавить себя в друзья.');
    }

    // Check if friendship already exists
    $existingFriendship = \DB::table('friendships')
        ->where([
            ['user_id', $user->id],
            ['friend_id', $friend->id]
        ])
        ->first();

    if ($existingFriendship) {
        // Remove friendship
        \DB::table('friendships')
            ->where([
                ['user_id', $user->id],
                ['friend_id', $friend->id]
            ])
            ->delete();

        // Also remove the reverse friendship
        \DB::table('friendships')
            ->where([
                ['user_id', $friend->id],
                ['friend_id', $user->id]
            ])
            ->delete();

        return redirect()->back()->with('success', 'Друг успешно удален.');
    } else {
        // Create friendship
        \DB::table('friendships')->insert([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create reverse friendship (mutual friendship)
        \DB::table('friendships')->insert([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Друг успешно добавлен.');
    }
}
```

### 4. Добавлены API-маршруты для пользователей
- **Файл**: `routes/api.php`
- **Изменения**:
  - Добавлены API-маршруты для управления пользователями
- **Код**:
```php
// API routes for Users
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware('auth:api');
Route::post('/users/{user}/toggle-friendship', [\App\Http\Controllers\Api\UserController::class, 'toggleFriendship'])->middleware('auth:api');
```

### 5. Создан API-контроллер для управления пользователями
- **Файл**: `app/Http/Controllers/Api/UserController.php`
- **Изменения**:
  - Методы `index()` и `show()` для просмотра пользователей
  - Метод `toggleFriendship()` для добавления/удаления друзей через API
- **Код**:
```php
public function index(): JsonResponse
{
    $user = Auth::user();

    $users = User::where('id', '!=', $user->id) // Exclude current user from list
                ->paginate(10);

    // Add friendship status to each user
    $users->getCollection()->transform(function ($userItem) use ($user) {
        $is_friend = $user->following()->where('friend_id', $userItem->id)->exists();
        $userItem->setAttribute('is_friend', $is_friend);
        return $userItem;
    });

    return response()->json([
        'success' => true,
        'data' => UserResource::collection($users)
    ]);
}

public function toggleFriendship(Request $request, User $user): JsonResponse
{
    $authUser = Auth::user();

    if (!$authUser) {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required.'
        ], 401);
    }

    if ($authUser->id == $user->id) {
        return response()->json([
            'success' => false,
            'message' => 'You cannot add yourself as a friend.'
        ], 400);
    }

    // Check if friendship already exists
    $existingFriendship = \DB::table('friendships')
        ->where([
            ['user_id', $authUser->id],
            ['friend_id', $user->id]
        ])
        ->first();

    if ($existingFriendship) {
        // Remove friendship
        \DB::table('friendships')
            ->where([
                ['user_id', $authUser->id],
                ['friend_id', $user->id]
            ])
            ->delete();

        // Also remove the reverse friendship
        \DB::table('friendships')
            ->where([
                ['user_id', $user->id],
                ['friend_id', $authUser->id]
            ])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend successfully removed.',
            'is_friend' => false
        ]);
    } else {
        // Create friendship
        \DB::table('friendships')->insert([
            'user_id' => $authUser->id,
            'friend_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create reverse friendship (mutual friendship)
        \DB::table('friendships')->insert([
            'user_id' => $user->id,
            'friend_id' => $authUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Friend successfully added.',
            'is_friend' => true
        ]);
    }
}
```

### 6. Создан ресурс для пользователей
- **Файл**: `app/Http/Resources/UserResource.php`
- **Изменения**:
  - Создан ресурс для сериализации пользовательских данных
- **Код**:
```php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'username' => $this->username,
        'email' => $this->email,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'is_friend' => $this->is_friend ?? false,
        'items' => ItemResource::collection($this->whenLoaded('items')),
    ];
}
```

### 7. Обновлены шаблоны для отображения дружбы
- **Файл**: `resources/views/layouts/app.blade.php`
- **Изменения**:
  - Добавлена ссылка на страницу просмотра пользователей для обычных пользователей
- **Код**:
```html
<a class="btn btn-outline-info me-2" href="{{ route('users.browse') }}">Пользователи</a>
@if(Auth::user()->is_admin)
    <a class="btn btn-outline-info me-2" href="{{ route('users.index') }}">Все пользователи</a>
@endif
```

### 8. Обновлено отображение объектов, чтобы пользователи могли просматривать чужие объекты
- **Файл**: `app/Http/Controllers/ItemController.php`
- **Изменения**:
  - Обновлен метод `show()` для разрешения просмотра любых объектов аутентифицированным пользователям
- **Код**:
```php
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
```

### 9. Функциональность реализованных критериев

#### Базовый уровень:
1. ✅ **Пользователи могут просматривать других пользователей** - Реализована страница `/users/browse` для просмотра всех пользователей
2. ✅ **Пользователи могут добавлять других пользователей в друзья** - Реализована кнопка добавления в друзья на странице просмотра пользователей
3. ✅ **Пользователи могут просматривать страницы других пользователей** - Реализована страница `/users/{user}` для просмотра объектов другого пользователя
4. ✅ **Обычные пользователи не могут редактировать/удалять данные других пользователей** - Реализована проверка прав доступа в контроллерах, разрешающая редактирование/удаление только владельцам и администраторам

#### Расширенный уровень:
1. ✅ **Критерии базового уровня** - Все реализованы
2. ✅ **Реализована REST API для управления пользователями** - Созданы API-маршруты и контроллеры для просмотра пользователей и управления дружбой
3. ✅ **При отображении объектов показывается статус дружбы с владельцем** - Добавлено отображение бейджа "Друг" на страницах просмотра объектов
4. ✅ **Разграничение прав доступа между обычными пользователями и администраторами** - Обычные пользователи могут только просматривать и добавлять друзей, но не могут редактировать/удалять чужие объекты

## Вывод
Все требования базового и расширенного уровней выполнены. Теперь обычные пользователи могут просматривать других пользователей, добавлять их в друзья и просматривать их страницы, при этом у них нет возможности редактировать или удалять чужие данные. Администраторы по-прежнему имеют полный доступ ко всем функциям системы.