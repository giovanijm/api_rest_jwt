<?php

namespace App\Traits;

trait HasTwoFactor
{
    /**
     * Generate and persist a new two-factor code and expiration.
     *
     * @return void
     */
    public function regenerateTwoFactorCode(string $method): void
    {
        $this->timestamps               = false; // Disable timestamps for this operation
        $this->two_factor_method        = $method;
        $this->two_factor_code          = (string) random_int(100000, 999999);
        $this->two_factor_expires_at    = now()->addMinutes(10);
        $this->save();
    }

    /**
     * Clear two-factor code and expiration.
     *
     * @return void
     */
    public function clearTwoFactorCode(): void
    {
        $this->timestamps               = false; // Disable timestamps for this operation
        $this->two_factor_method        = null;
        $this->two_factor_code          = null;
        $this->two_factor_expires_at    = null;
        $this->save();
    }
}
