<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'trade_date' => 'date:Y-m-d',
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(IncomeDetail::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, IncomeDetail::class, 'income_id', 'id', 'id', 'order_id');
    }

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }
}
