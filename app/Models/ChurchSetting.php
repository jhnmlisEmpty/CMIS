<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ChurchSetting extends Model
{
    use Auditable;

    public const DATE_FORMATS = ['M j, Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'];
    public const LOCALES = ['en', 'fil'];

    protected $fillable = ['name', 'short_name', 'logo_path', 'address', 'phone', 'email', 'website', 'timezone', 'locale', 'date_format'];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], self::defaultValues());
    }

    public static function fallback(): self
    {
        return new self(self::defaultValues());
    }

    private static function defaultValues(): array
    {
        return [
            'name' => 'True Vine World Harvest Church - Pangasinan',
            'short_name' => 'TVWHC Pangasinan',
            'timezone' => 'Asia/Manila',
            'locale' => 'en',
            'date_format' => 'M j, Y',
        ];
    }
}
