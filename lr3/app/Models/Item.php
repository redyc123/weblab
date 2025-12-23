<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price',
        'image',
        'released_at',
        'category',
        'user_id',
    ];

    // кастинг дат
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'released_at',
    ];

    // Мутатор: при установке released_at принимать разные форматы
    public function setReleasedAtAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['released_at'] = null;
            return;
        }

        // если строка — попытаемся распарсить
        $date = Carbon::parse($value);
        $this->attributes['released_at'] = $date->format('Y-m-d');
    }

    // Аксессор: вывод в удобном формате
    public function getReleasedAtFormattedAttribute()
    {
        if (!$this->released_at) return null;
        return Carbon::parse($this->released_at)->format('d.m.Y');
    }

    // Связь: элемент принадлежит пользователю
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Boot the model and attach event listeners to check permissions
     */
    protected static function boot()
    {
        parent::boot();

        // Check permissions before updating
        static::updating(function ($item) {
            if (Auth::check()) {
                $user = Auth::user();
                // Only the owner or admin can update the item
                if ($item->user_id !== $user->id && !$user->is_admin) {
                    abort(403, 'You do not have permission to update this item.');
                }
            } else {
                abort(403, 'You must be authenticated to update this item.');
            }
        });

        // Check permissions before deleting (soft delete)
        static::deleting(function ($item) {
            if (Auth::check()) {
                $user = Auth::user();
                // Only the owner or admin can delete the item
                if ($item->user_id !== $user->id && !$user->is_admin) {
                    abort(403, 'You do not have permission to delete this item.');
                }
            } else {
                abort(403, 'You must be authenticated to delete this item.');
            }
        });
    }
}
