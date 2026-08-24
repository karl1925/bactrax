<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set or update a setting value.
     */
    public static function set(string $key, $value, string $group = 'general', ?string $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
                'description' => $description,
            ]
        );
    }

    /**
     * Atomically get the next formatted PR tracking number and increment the database counter.
     * Output format example: PR-202608-0001
     */
    /**
     * Atomically get the next formatted PR tracking number and increment the database counter.
     * Output format example: PR-2026-00005
     */
    public static function getNextPrNumberAndIncrement(): string
    {
        return DB::transaction(function () {
            // Lock row for update to prevent concurrent duplicate numbers
            $setting = static::where('key', 'pr_next_number')->lockForUpdate()->first();

            if (!$setting) {
                $setting = static::create([
                    'key' => 'pr_next_number',
                    'value' => '1',
                    'group' => 'procurement',
                    'description' => 'Next sequential number for Purchase Requests',
                ]);
            }

            $currentNumber = (int) $setting->value;
            $padding = (int) static::get('pr_number_padding', 5); // Default 5 digits padding
            $prefix = static::get('pr_prefix', 'PR-');

            // Increment database counter for next PR
            $setting->value = (string) ($currentNumber + 1);
            $setting->save();

            // Format: PREFIX + YEAR + - + PADDED_NUMBER (e.g. PR-2026-00005)
            return $prefix . date('Y') . '-' . str_pad($currentNumber, $padding, '0', STR_PAD_LEFT);
        });
    }
}
