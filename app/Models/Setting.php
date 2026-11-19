<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Settings administrators can change. Each key overrides one value from config/medimatch.php.
 * Safety rules that must never be switched off (listing review, prescription checks) are not settings.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];

    /** setting key => [config key, type, min, max] */
    public const MAP = [
        'min_shelf_life_months' => ['medimatch.listing_rules.min_shelf_life_months', 'int', 1, 24],
        'max_quantity' => ['medimatch.listing_rules.max_quantity', 'int', 1, 10000],
        'max_photos' => ['medimatch.listing_rules.max_photos', 'int', 1, 10],
        'request_expiry_days' => ['medimatch.request_expiry_days', 'int', 7, 365],
        'allow_affordable_access' => ['medimatch.allow_affordable_access', 'bool', null, null],
    ];

    /** Copy stored settings over the config defaults. Called once per request at start-up. */
    public static function applyToConfig(): void
    {
        try {
            $stored = static::query()->pluck('value', 'key');
        } catch (Throwable) {
            return; // table not created yet (fresh install, or before migrations in tests)
        }

        foreach ($stored as $key => $value) {
            if (isset(self::MAP[$key])) {
                [$configKey, $type] = self::MAP[$key];
                config([$configKey => $type === 'bool' ? (bool) $value : (int) $value]);
            }
        }
    }

    /** Current values (stored or default), for the settings form. */
    public static function current(): array
    {
        return collect(self::MAP)->map(fn ($m) => config($m[0]))->all();
    }

    public static function put(string $key, int|bool $value): void
    {
        $setting = static::query()->findOrNew($key);
        $setting->key = $key;
        $setting->value = (string) (int) $value;
        $setting->save();
    }
}
