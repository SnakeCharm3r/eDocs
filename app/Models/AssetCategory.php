<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'tag_prefix',
    ];

    /**
     * Get all assets in this category
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
