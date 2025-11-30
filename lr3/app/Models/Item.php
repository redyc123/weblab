<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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
}
