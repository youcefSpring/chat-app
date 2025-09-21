<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'initiated_by',
        'type',
        'status',
        'started_at',
        'ended_at',
        'sfu_room_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'call_participants')
            ->withPivot('joined_at', 'left_at', 'status')
            ->withTimestamps();
    }

    public function callParticipants(): HasMany
    {
        return $this->hasMany(CallParticipant::class);
    }
}