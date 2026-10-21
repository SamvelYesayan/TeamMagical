<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const EMPLOYEE = 'employee';

    protected $fillable = ['slug', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
