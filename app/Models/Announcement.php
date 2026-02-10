<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'content', 'pdf_path', 'userId'];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
