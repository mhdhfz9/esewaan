<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'tarikh_tindakan',
        'jenis_tindakan',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tarikh_tindakan' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function rentalContract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
