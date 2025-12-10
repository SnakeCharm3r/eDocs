<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IDCards extends Model
{
    use HasFactory;
// Define the table name explicitly if it's not plural of the model name
protected $table = 'id_card_requests';

protected $fillable = [
    'user_id',
];

// Define relationships to other models
public function user()
{
    return $this->belongsTo(User::class, 'user_id');

}

}
