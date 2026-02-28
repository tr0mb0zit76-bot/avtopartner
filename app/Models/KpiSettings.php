<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSettings extends Model
{
    protected $table = 'kpi_settings';
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description'
    ];

    protected $casts = [
        'value' => 'string'
    ];

    /**
     * Получить значение настройки
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value
        };
    }

    /**
     * Установить значение настройки
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        $value = match($type) {
            'json' => json_encode($value),
            default => (string) $value
        };

        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description
            ]
        );
    }
}