<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'content', 'pdf_path', 'userId', 'view_count'];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'announcement_user_views')
            ->withPivot('viewed_at')
            ->withTimestamps();
    }
}
