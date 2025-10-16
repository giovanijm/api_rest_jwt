<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Uid\Ulid;

trait HasUlidsCustom
{
    // Do not import the framework HasUlids trait here to avoid its own boot logic

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * Generate a ULID and return it in uppercase.
     *
     * @return string
     */
    public function generateUlid(): string
    {
        // Use Symfony Ulid generator to ensure canonical ULID generation
        return strtoupper((string) Ulid::generate());
    }

    /**
     * Boot the trait and assign ULIDs to the model's primary key in uppercase.
     */
    protected static function bootHasUlidsCustom(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();

            if (empty($model->{$key})) {
                // If HasUlids trait defines generateUlid, prefer that, otherwise use our implementation
                if (method_exists($model, 'generateUlid') && (new \ReflectionClass($model))->hasMethod('generateUlid')) {
                    // Call the model's generateUlid (this method is the one we define here)
                    $model->{$key} = (string) $model->generateUlid();
                } else {
                    $model->{$key} = strtoupper((string) Ulid::generate());
                }
            }
        });
    }
}
