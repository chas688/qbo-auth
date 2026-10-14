<?php

namespace CharlesHarmon\QboAuth\Models;

use Illuminate\Database\Eloquent\Model;

class QuickBooksConnection extends Model
{
    protected $fillable = [
        'realm_id',
        'environment',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
        ];
    }
}
