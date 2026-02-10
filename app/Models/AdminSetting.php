<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get setting value with proper type casting
     */
    public function getCastedValue()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'json':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Get all settings as key-value array
     */
    public static function getAllSettings(): array
    {
        return static::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->getCastedValue()];
        })->toArray();
    }

    /**
     * Get setting by key
     */
    public static function getSetting(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        return $setting ? $setting->getCastedValue() : $default;
    }

    /**
     * Set setting value
     */
    public static function setSetting(string $key, $value, string $type = 'string', string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }
}
