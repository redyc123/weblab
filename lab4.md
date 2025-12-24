# Лабораторная работа №4 - Отчет

## Тема: Многопользовательское приложение

### Выполненные изменения:

## 1. Установка Laravel Breeze
- **Файл**: `composer.json`
- **Изменения**: Установлен пакет `laravel/breeze` для аутентификации
- **Команда**: `composer require laravel/breeze --dev`
- **Результат**: Добавлены стандартные аутентификационные маршруты и представления

## 2. Модификация структуры базы данных
### 2.1. Добавление связи пользователь-элемент
- **Файл**: `database/migrations/2025_12_23_064908_add_user_id_to_items_table.php`
- **Изменения**:
  - Добавление столбца `user_id` в таблицу `items`
  - Создание внешнего ключа, связывающего элементы с пользователями
- **Код**:
```php
Schema::table('items', function (Blueprint $table) {
    $table->unsignedBigInteger('user_id')->nullable()->after('id');
});
Schema::table('items', function (Blueprint $table) {
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});
```

### 2.2. Добавление администраторского флага
- **Файл**: `database/migrations/2025_12_23_065215_add_is_admin_to_users_table.php`
- **Изменения**:
  - Добавление столбца `is_admin` в таблицу `users`
  - Установка значения по умолчанию `false`
- **Код**:
```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_admin')->default(false);
});
```

### 2.3. Добавление уникального поля username (для маршрутизации по имени пользователя)
- **Файл**: `database/migrations/2025_12_23_065922_add_username_to_users_table.php`
- **Изменения**:
  - Добавление уникального поля `username` в таблицу `users`
  - Заполнение его значениями на основе поля `name`
- **Код**:
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('username')->unique()->nullable();
});
```

### 2.4. Включение Soft Deletes в таблице items
- **Файл**: `app/Models/Item.php`
- **Изменения**:
  - Добавлен трейт `SoftDeletes` для возможности мягкого удаления
- **Код**:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    // ...
}
```

## 3. Модели

### 3.1. Изменения в модели Item
- **Файл**: `app/Models/Item.php`
- **Изменения**:
  - Добавление связи `belongsTo` с моделью User
  - Добавление поля `user_id` в массив `$fillable`
  - Добавление трейта `SoftDeletes` для мягкого удаления
  - Добавление событий модели (events/closures) для проверки разрешений при обновлении и удалении
- **Код**:
```php
public function user(): BelongsTo
{
    return $this->belongsTo(\App\Models\User::class);
}

protected static function boot()
{
    parent::boot();

    static::updating(function ($item) {
        if (Auth::check()) {
            $user = Auth::user();
            if ($item->user_id !== $user->id && !$user->is_admin) {
                abort(403, 'You do not have permission to update this item.');
            }
        } else {
            abort(403, 'You must be authenticated to update this item.');
        }
    });

    static::deleting(function ($item) {
        if (Auth::check()) {
            $user = Auth::user();
            if ($item->user_id !== $user->id && !$user->is_admin) {
                abort(403, 'You do not have permission to delete this item.');
            }
        } else {
            abort(403, 'You must be authenticated to delete this item.');
        }
    });
}
```

### 3.2. Изменения в модели User
- **Файл**: `app/Models/User.php`
- **Изменения**:
  - Добавление поля `username` и `is_admin` в массив `$fillable`
  - Установка `username` как ключа маршрута
- **Код**:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',
    'username',
];

public function getRouteKeyName()
{
    return 'username';
}
```

## 4. Контроллеры

### 4.1. Изменения в ItemController
- **Файл**: `app/Http/Controllers/ItemController.php`
- **Изменения**:
  - Добавление метода `userItems()` для отображения элементов конкретного пользователя
  - Модификация метода `index()` для отображения только элементов текущего пользователя
  - Изменение метода `store()` для автоматического сохранения `user_id`
  - Добавление проверок разрешений в методы `show()`, `edit()`, `update()`, `destroy()`
  - Добавление метода `usersIndex()` для отображения списка всех пользователей
  - Добавление методов для административных действий: `forceDestroy()`, `restore()`, `trashed()`
- **Обновленный код метода forceDestroy** (исправлен для работы с мягко удаленными элементами):
```php
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
```

## 5. Маршруты
- **Файл**: `routes/web.php`
- **Изменения**:
  - Обернуты все маршруты элементов в группу с middleware `auth`
  - Добавлен маршрут `/users/{user}` для просмотра элементов конкретного пользователя
  - Добавлен маршрут `/users` для просмотра списка пользователей
  - Добавлены маршруты для административных функций
- **Код**:
```php
Route::middleware(['auth'])->group(function () {
    // Routes for admin functions - define these BEFORE the resource route
    // to prevent route model binding conflicts
    Route::get('/items/trashed', [ItemController::class, 'trashed'])->name('items.trashed');
    Route::delete('/items/{id}/force', [ItemController::class, 'forceDestroy'])->name('items.forceDestroy');
    // Also add a POST route for force delete (for forms that don't support method spoofing)
    Route::post('/items/{id}/force', [ItemController::class, 'forceDestroy'])->name('items.forceDestroy.post');
    Route::post('/items/{id}/restore', [ItemController::class, 'restore'])->name('items.restore');

    // Now define the resource route after specific routes
    Route::resource('items', ItemController::class);

    Route::get('/users', [ItemController::class, 'usersIndex'])->name('users.index');
    Route::get('/users/{user}', [ItemController::class, 'userItems'])->name('users.items');
    // ...
});
```

## 6. Представления (Views)
- **Файлы**: `resources/views/items/*.blade.php`
- **Изменения**:
  - Добавлены проверки разрешений в представлениях для отображения/скрытия кнопок действий
  - Добавлена информация о пользователе-владельце в админской части
  - Создан шаблон `trashed.blade.php` для отображения мягко удаленных элементов
  - Добавлены формы для восстановления и постоянного удаления элементов в trashed.blade.php
- **Пример кода формы для постоянного удаления**:
```blade
<form method="POST" action="{{ route('items.forceDestroy.post', $item) }}" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите навсегда удалить этот элемент?')">
    @csrf
    <button type="submit" class="btn btn-sm btn-danger" title="Удалить навсегда">
        Удалить навсегда
    </button>
</form>
```

## 7. Авторизация (Gates)
- **Файл**: `app/Providers/AuthServiceProvider.php`
- **Изменения**:
  - Добавлены gates для различных операций (update-item, delete-item, force-delete-item, restore-item, view-user-items)
- **Код**:
```php
Gate::define('update-item', function ($user, $item) {
    return $item->user_id === $user->id || $user->is_admin;
});

Gate::define('force-delete-item', function ($user, $item) {
    return $user->is_admin;
});
```

## 8. Сеялки (Seeders)
- **Файлы**: `database/seeders/UserSeeder.php`, `database/seeders/DatabaseSeeder.php`
- **Изменения**:
  - Создан UserSeeder для создания пользователей с разными правами
  - Создан администраторский пользователь
  - Обновлен DatabaseSeeder для запуска UserSeeder

## 9. Шаблоны (Layouts и Views)
- **Файл**: `resources/views/layouts/app.blade.php`
- **Изменения**:
  - Восстановлена оригинальная стилистика навигации
  - Добавлена навигация для администраторов (ссылка на список пользователей)
  - Добавлено отображение статуса администратора

- **Файл**: `resources/views/layouts/guest.blade.php`
- **Изменения**:
  - Заменен Vite на Mix для соответствия оригинальной стилистике приложения
  - Заменена переменная $slot на @yield('content') для правильной работы шаблона
  - Обновлены шаблоны для аутентификации (login, register, forgot-password, reset-password, verify-email, confirm-password)
  - Все аутентификационные страницы переведены на Bootstrap стиль вместо Breeze компонентов

- **Файлы**: `resources/views/auth/*.blade.php`
- **Изменения**:
  - Обновлены все аутентификационные страницы (login, register, forgot-password, reset-password, verify-email, confirm-password)
  - Заменены Breeze компоненты на Bootstrap компоненты
  - Переведен текст на русский язык
  - Сохранена оригинальная стилистика приложения

## 10. Особенности реализации функции "удалить навсегда"
### 10.1. Проблема и решение
- **Проблема**: При нажатии на кнопку "Удалить навсегда" в `/items/trashed` происходил 404 ошибка
- **Причина**: Мягко удаленные элементы не находились через стандартную маршрутизацию Laravel из-за использования `SoftDeletes` и route model binding
- **Решение**: 
  1. Изменение контроллера `forceDestroy` для поиска элементов с `withTrashed()`
  2. Исправление маршрутов для использования `{id}` вместо `{item}`
  3. Добавление POST маршрута параллельно DELETE для поддержки HTML-форм
  4. Исправление синтаксических ошибок в именованных аргументах

### 10.2. Дополнительные исправления
- **Проблема**: Отсутствовал маршрут `users.index` который использовался в layout-файле
- **Решение**: Добавлен маршрут для `users.index` с соответствующим методом в контроллере

## 11. Тестирование
- **Успешно протестирована регистрация и аутентификация пользователей**
- **Проверена работа Soft Deletes - обычные пользователи могут мягко удалять свои элементы**
- **Проверена работа восстановления элементов администратором**
- **Проверена работа административных функций (полное удаление, просмотр всех элементов)**
- **Проверена защита от несанкционированного доступа к чужим элементам**
- **Протестирована функция постоянного удаления элементов (force delete), включая случаи мягкого удаления**

## 12. Реализованные функции расширенного уровня:
1. ✅ Laravel Breeze установлен, пользователь может зарегистрироваться и авторизоваться
2. ✅ После авторизации пользователь видит свои объекты
3. ✅ Добавлять, удалять и редактировать объекты может только авторизованный пользователь
4. ✅ При добавлении нового объекта в базу данных записывается ID пользователя
5. ✅ Реализован отдельный метод для вывода всех пользователей с возможностью навигации по ним
6. ✅ Реализован пользователь с правами администратора
7. ✅ Реализован механизм Soft Deletes
8. ✅ Проверка прав доступа пользователя реализована на уровне интерфейса, контроллера и модели
9. ✅ Ссылка для вывода объектов пользователя использует username вместо ID
10. ✅ Реализована функция "удалить навсегда" для мягко удаленных элементов
11. ✅ Функция "удалить навсегда" работает корректно с мягко удаленными элементами
12. ✅ Добавлены необходимые маршруты для административных функций

## Вывод
Все требования расширенного уровня выполнены. Создано многопользовательское приложение с системой аутентификации, авторизации и управлением правами доступа к данным. Особое внимание уделено исправлению функции "удалить навсегда", которая требует специальной обработки мягко удаленных элементов. Приложение полностью функционально и безопасно для использования.