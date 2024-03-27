<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Spatie\Permission;

class Menu extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [
        'id'
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function permission(): HasOne
    {
        return $this->hasOne(Permission::class, 'name', 'name');
    }
}
