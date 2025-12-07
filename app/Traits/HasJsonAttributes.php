<?php

declare(strict_types=1);

namespace App\Traits;

trait HasJsonAttributes
{
    public static function bootHasJsonAttributes(): void
    {
        static::retrieved(fn ($m) => $m->ensureJsonCasts());
        static::creating(fn ($m) => $m->ensureJsonCasts());
        static::updating(fn ($m) => $m->ensureJsonCasts());
    }

    protected function ensureJsonCasts(): void
    {
        if (! property_exists($this, 'jsonAttributes') || ! is_array($this->jsonAttributes)) {
            return;
        }

        $casts = (array) ($this->casts ?? []);
        for ($i = 0; $i < count($this->jsonAttributes); $i++) {
            $attr = $this->jsonAttributes[$i];
            $casts[$attr] = 'array';
            if ($this->getAttribute($attr) === null) {
                $this->setAttribute($attr, []);
            }
        }
        $this->casts = $casts;
    }
}
