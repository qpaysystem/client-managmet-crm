<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    protected $fillable = ['project_id', 'name', 'file_path'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return Storage::disk('public')->exists($this->file_path)
            ? asset('storage/' . $this->file_path)
            : null;
    }

    public function getFileNameAttribute(): ?string
    {
        return $this->file_path ? basename($this->file_path) : null;
    }
}
