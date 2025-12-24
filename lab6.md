# Лабораторная работа №6 - Отчет

## Тема: Реализация REST API в Laravel

### Выполненные изменения:

## 1. Установка Laravel Passport для OAuth2-аутентификации
### 1.1. Установка пакета Laravel Passport
- **Файлы**: `composer.json`, `composer.lock`
- **Изменения**:
  - Установка пакета `laravel/passport` через Composer
  - Обновление зависимостей проекта
- **Команда**: `composer require laravel/passport`

### 1.2. Публикация миграций и конфигурации
- **Файл**: `config/passport.php`
- **Папки**: `database/migrations`, `resources/views/vendor/passport`
- **Изменения**:
  - Публикация миграций для OAuth2 таблиц (oauth_clients, oauth_access_tokens, oauth_refresh_tokens и т.д.)
  - Публикация конфигурационного файла passport.php
  - Публикация представлений для управления токенами
- **Команда**: `php artisan vendor:publish --provider="Laravel\Passport\PassportServiceProvider"`

### 1.3. Выполнение миграций
- **Файлы**: `database/migrations/*_create_oauth_*_table.php`
- **Изменения**:
  - Создание необходимых таблиц для работы OAuth2
  - Установка связей между таблицами
- **Команда**: `php artisan migrate`

### 1.4. Установка Laravel Passport
- **Изменения**:
  - Создание ключей шифрования
  - Создание клиентов OAuth (personal access и password grant)
- **Команда**: `php artisan passport:install`

### 1.5. Настройка модели User
- **Файл**: `app/Models/User.php`
- **Изменения**:
  - Замена `use Laravel\Sanctum\HasApiTokens` на `use Laravel\Passport\HasApiTokens`
  - Подключение функциональности API токенов через Passport
- **Код**:
```php
use Laravel\Passport\HasApiTokens;
// ...
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ...
}
```

## 2. Создание API маршрутов
### 2.1. Обновление API маршрутов
- **Файл**: `routes/api.php`
- **Изменения**:
  - Добавление API ресурсов для Items и Comments
  - Обновление middleware с `auth:sanctum` на `auth:api`
  - Добавление middleware для аутентификации на API-маршруты
- **Код**:
```php
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for Items
Route::apiResource('items', \App\Http\Controllers\Api\ItemController::class)->middleware('auth:api');

// API routes for Comments
Route::apiResource('comments', \App\Http\Controllers\Api\CommentController::class)->middleware('auth:api');
```

## 3. Создание API контроллеров
### 3.1. Создание контроллера для Items API
- **Файл**: `app/Http/Controllers/Api/ItemController.php`
- **Изменения**:
  - Создание CRUD операций для Items через API
  - Реализация методов index, show, store, update, destroy
  - Добавление проверки прав доступа к ресурсам
  - Добавление метода myItems для получения элементов авторизованного пользователя
  - Возвращение JSON-ответов с использованием API Resources
- **Код для метода index**:
```php
public function index(): JsonResponse
{
    $items = Item::with(['user', 'comments'])->paginate(10);
    
    return response()->json([
        'success' => true,
        'data' => ItemResource::collection($items)
    ]);
}
```

### 3.2. Создание контроллера для Comments API
- **Файл**: `app/Http/Controllers/Api/CommentController.php`
- **Изменения**:
  - Создание CRUD операций для Comments через API
  - Реализация методов index, show, store, update, destroy
  - Добавление проверки прав доступа к ресурсам
  - Возвращение JSON-ответов с использованием API Resources
- **Код для метода store**:
```php
public function store(Request $request): JsonResponse
{
    $data = $request->validate([
        'content' => 'required|string|max:500',
        'item_id' => 'required|exists:items,id',
    ]);

    $user = Auth::user();
    $item = Item::findOrFail($request->item_id);

    $comment = new Comment();
    $comment->content = $request->content;
    $comment->user_id = $user->id;
    $comment->item_id = $request->item_id;
    $comment->save();

    return response()->json([
        'success' => true,
        'data' => new CommentResource($comment),
        'message' => 'Comment created successfully.'
    ], 201);
}
```

## 4. Создание API Resources
### 4.1. Создание директории Resources
- **Папка**: `app/Http/Resources`
- **Изменения**:
  - Создание директории для API Resources

### 4.2. Создание ItemResource
- **Файл**: `app/Http/Resources/ItemResource.php`
- **Изменения**:
  - Создание ресурса для формирования структуры ответа для Items
  - Добавление связанных данных (user, comments)
  - Добавление флага is_friend для определения статуса дружбы
- **Код**:
```php
public function toArray($request)
{
    $user = Auth::user();
    $is_friend = false;
    
    // Check if the user is friend of the item owner
    if ($user) {
        $is_friend = $user->following()->where('friend_id', $this->user_id)->exists();
    }
    
    return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'price' => $this->price,
        'image' => $this->image ? asset('storage/' . $this->image) : null,
        'released_at' => $this->released_at,
        'category' => $this->category,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'deleted_at' => $this->deleted_at,
        'is_friend' => $is_friend,
        'user' => [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'username' => $this->user->username,
        ],
        'comments' => CommentResource::collection($this->whenLoaded('comments')),
    ];
}
```

### 4.3. Создание CommentResource
- **Файл**: `app/Http/Resources/CommentResource.php`
- **Изменения**:
  - Создание ресурса для форматирования ответа для Comments
  - Добавление связанных данных (user, item)
  - Добавление флага is_friend для определения статуса дружбы
- **Код**:
```php
public function toArray($request)
{
    $user = Auth::user();
    $is_friend = false;
    
    // Check if the user is friend of the comment owner
    if ($user) {
        $is_friend = $user->following()->where('friend_id', $this->user_id)->exists();
    }
    
    return [
        'id' => $this->id,
        'content' => $this->content,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'is_friend' => $is_friend,
        'user' => [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'username' => $this->user->username,
        ],
        'item' => [
            'id' => $this->item->id,
            'title' => $this->item->title,
            'description' => $this->item->description,
            'price' => $this->item->price,
            'created_at' => $this->item->created_at,
        ],
    ];
}
```

## 5. Реализация флага is_friend
### 5.1. Добавление флага в ItemResource
- **Файл**: `app/Http/Resources/ItemResource.php`
- **Изменения**:
  - Проверка отношения дружбы между текущим пользователем и владельцем элемента
  - Добавление поля is_friend в JSON-ответ

### 5.2. Добавление флага в CommentResource
- **Файл**: `app/Http/Resources/CommentResource.php`
- **Изменения**:
  - Проверка отношения дружбы между текущим пользователем и владельцем комментария
  - Добавление поля is_friend в JSON-ответ

### 5.3. Добавление флага в ItemController
- **Файл**: `app/Http/Controllers/Api/ItemController.php`
- **Изменения**:
  - Добавление логики проверки дружбы в метод show

## 6. Создание профильной страницы для API токенов
### 6.1. Создание контроллера профиля
- **Файл**: `app/Http/Controllers/ProfileController.php`
- **Изменения**:
  - Создание методов для отображения профиля
  - Создание методов для управления API токенами
- **Методы**:
  - `index()` - отображение профиля пользователя и токенов
  - `createToken()` - создание нового API токена
  - `deleteToken()` - удаление API токена

### 6.2. Добавление маршрутов профиля
- **Файл**: `routes/web.php`
- **Изменения**:
  - Добавление маршрутов для профиля и управления токенами
- **Код**:
```php
// Profile route for API tokens
Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::post('/profile/token', [ProfileController::class, 'createToken'])->name('profile.token.create');
Route::delete('/profile/token/{tokenId}', [ProfileController::class, 'deleteToken'])->name('profile.token.delete');
```

### 6.3. Создание представления профиля
- **Файл**: `resources/views/profile/index.blade.php`
- **Изменения**:
  - Создание страницы профиля с информацией о пользователе
  - Форма для создания API токенов
  - Список существующих токенов с возможностью удаления
  - JavaScript для асинхронного управления токенами

### 6.4. Добавление ссылки на профиль в навигацию
- **Файл**: `resources/views/layouts/app.blade.php`
- **Изменения**:
  - Добавление кнопки "Профиль" в навигационную панель
- **Код**:
```php
<a class="btn btn-outline-primary me-2" href="{{ route('profile.index') }}">Профиль</a>
```

## 7. Функциональность реализованных критериев

### Базовый уровень:
1. ✅ **Laravel Passport генерирует токены для каждого пользователя** - Установлен и настроен Laravel Passport
2. ✅ **Реализован метод GET для основной и вспомогательной сущности** - Реализованы методы index и show для Items и Comments в API

### Расширенный уровень:
1. ✅ **Критерии базового уровня** - Все реализованы
2. ✅ **Реализованы методы POST и PUT для основной и вспомогательной сущности** - Реализованы методы store и update для Items и Comments
3. ✅ **При выводе в данные вспомогательной сущности добавляются данные основной** - В CommentResource добавлены данные Item
4. ✅ **В данных REST отдельным полем передается признак "друга", если владелец записи является другом пользователя** - Добавлено поле is_friend в ItemResource и CommentResource

## 8. Примеры использования API через curl
### 8.1. Получение списка элементов
```bash
curl -X GET "http://localhost:8000/api/items" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 8.2. Получение конкретного элемента
```bash
curl -X GET "http://localhost:8000/api/items/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 8.3. Создание нового элемента
```bash
curl -X POST "http://localhost:8000/api/items" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Новый тур",
    "description": "Описание нового тура",
    "price": 1000,
    "category": "путешествия",
    "released_at": "2025-12-24"
  }'
```

### 8.4. Обновление элемента
```bash
curl -X PUT "http://localhost:8000/api/items/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Обновленный тур",
    "description": "Обновленное описание тура",
    "price": 1200
  }'
```

### 8.5. Удаление элемента
```bash
curl -X DELETE "http://localhost:8000/api/items/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 8.6. Получение списка комментариев
```bash
curl -X GET "http://localhost:8000/api/comments" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 8.7. Создание нового комментария
```bash
curl -X POST "http://localhost:8000/api/comments" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Отличный тур!",
    "item_id": 1
  }'
```

### 8.8. Получение профиля пользователя (требует токен)
```bash
curl -X GET "http://localhost:8000/api/user" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

## Вывод
Все требования базового и расширенного уровней выполнены. Создано REST API с использованием Laravel Passport для OAuth2-аутентификации. Реализованы полные CRUD-операции для основной сущности (Items) и вспомогательной сущности (Comments). API возвращает данные в формате JSON с использованием API Resources, позволяя включать связанные данные и информацию о дружбе между пользователями. Пользователи могут создавать и управлять своими API токенами через профильную страницу.