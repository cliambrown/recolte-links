<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'name',
    ];
    
    public function links() {
        return $this->belongsToMany(Link::class, 'link_tags');
    }
}
