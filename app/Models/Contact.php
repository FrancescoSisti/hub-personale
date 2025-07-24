<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'phone',
        'company',
        'origin',
        'extra_data',
        'ip_address',
        'user_agent',
        'read',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('read', true);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFromOrigin(Builder $query, string $origin): Builder
    {
        return $query->where('origin', $origin);
    }

    public function markAsRead(): bool
    {
        return $this->update(['read' => true]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read' => false]);
    }
}
