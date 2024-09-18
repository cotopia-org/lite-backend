<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    public const STATUSES = [
        'in_progress',
        'paused',
        'completed',
    ];

    protected $fillable = [
        'workspace_id',
        'title',
        'description',
        'status',
        'end_at',
    ];

    protected $casts = [
        'end_at' => 'datetime',
    ];

    public function joinUser($user, $role = 'developer')
    {
        if (! $this->users->contains($user->id)) {
            $this->users()->attach($user, ['role' => $role]);
            //TODO: Socket, user joined to job.

        }

        return $this;

    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
