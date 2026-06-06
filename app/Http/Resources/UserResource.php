<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->maskEmail($this->email),
            'email_verified' => (bool) $this->email_verified_at,
            'role'           => $this->role,
            'phone_number'   => $this->maskPhone($this->phone_number),
            'phone_verified' => (bool) $this->phone_verified_at,
            'location'       => $this->location,
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Mascara o e-mail conforme LGPD.
     * Exemplo: giovanijm@gmail.com → g*******m@g****.com
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        $maskedLocal = match (true) {
            strlen($local) <= 1 => '*',
            strlen($local) === 2 => $local[0] . '*',
            default              => $local[0] . str_repeat('*', strlen($local) - 2) . $local[-1],
        };

        $dotPos       = strrpos($domain, '.');
        $domainName   = substr($domain, 0, $dotPos);
        $tld          = substr($domain, $dotPos);

        $maskedDomain = strlen($domainName) <= 1
            ? str_repeat('*', strlen($domainName))
            : $domainName[0] . str_repeat('*', strlen($domainName) - 1);

        return "{$maskedLocal}@{$maskedDomain}{$tld}";
    }

    /**
     * Mascara o telefone conforme LGPD.
     * Exemplo: 12992061431 → 129****1431
     */
    private function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        $len    = strlen($digits);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        // Mantém os 3 primeiros (DDD + 1º dígito) e os 4 últimos; mascara o meio
        $head   = 3;
        $tail   = 4;
        $masked = max(0, $len - $head - $tail);

        return substr($digits, 0, $head) . str_repeat('*', $masked) . substr($digits, -$tail);
    }
}
