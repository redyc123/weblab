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