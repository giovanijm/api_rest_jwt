<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\HasUlidsCustom;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use App\Traits\HasTwoFactor;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUlidsCustom, HasTwoFactor;

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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'is_active'             => 'boolean',
            'two_factor_expires_at' => 'datetime',
            'phone_verified_at'     => 'datetime',
        ];
    }

    /**
     * Accessor for location returning array with lat/lon or null.
     *
     * @return array|null
     */
    public function getLocationAttribute(): ?array
    {
        if (! $this->attributes['location'] ?? false) {
            return null;
        }

        $point = DB::selectOne('SELECT ST_X(location::geometry) AS lon, ST_Y(location::geometry) AS lat FROM users WHERE id = ?', [$this->getKey()]);

        if (! $point) {
            return null;
        }

        return ['lat' => (float) $point->lat, 'lon' => (float) $point->lon];
    }

    /**
     * Mutator for location: accepts null or array ['lat'=>..., 'lon'=>...] and persists value.
     *
     * @param  array|null  $value
     * @return void
     */
    public function setLocationAttribute(?array $value): void
    {
        if (is_null($value)) {
            // set NULL in DB
            DB::statement('UPDATE users SET location = NULL WHERE id = ?', [$this->getKey()]);
            $this->attributes['location'] = null;
            return;
        }

        $lat = $value['lat'] ?? null;
        $lon = $value['lon'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lon)) {
            throw new \InvalidArgumentException('Location must be an array with numeric keys "lat" and "lon".');
        }

        // Persist geography(Point,4326)
        DB::statement('UPDATE users SET location = ST_SetSRID(ST_Point(?, ?), 4326)::geography WHERE id = ?', [$lon, $lat, $this->getKey()]);

        // keep attribute in model instance
        $this->attributes['location'] = true;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     * 
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * 
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'role'              => $this->role,
            'is_active'         => $this->is_active,
            'phone_verified'    => ! is_null($this->phone_verified_at),
            'email_verified'    => ! is_null($this->email_verified_at),
        ];
    }

    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }
}