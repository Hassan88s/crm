<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'name', 'token_hash', 'token_prefix', 'scopes',
        'last_used_at', 'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    /**
     * Generate a new API key. Returns [ApiKey $model, string $plainToken].
     * The plain token is only returned here — never stored in DB.
     */
    public static function generate(string $name, string $scopes = '*'): array
    {
        $plain = 'pk_' . Str::random(48);
        $model = self::create([
            'name'         => $name,
            'token_hash'   => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 12),
            'scopes'       => $scopes,
        ]);
        return [$model, $plain];
    }

    public static function findByPlainToken(string $plain): ?self
    {
        return self::where('token_hash', hash('sha256', $plain))
            ->whereNull('revoked_at')
            ->first();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
