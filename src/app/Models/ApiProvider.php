<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiProvider extends Model
{
    use HasFactory;

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'api_providers';

    /**
     * Атрибуты, для которых разрешено массовое заполнение.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'base_url',
        'status',
        'credentials',
    ];

    /**
     * Преобразования атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
        ];
    }

    /**
     * Проверка, активен ли провайдер.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope для фильтрации активных провайдеров.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
