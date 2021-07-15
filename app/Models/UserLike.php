<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLike extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'link_id',
        'user_id',
    ];
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function link() {
        return $this->belongsTo(Link::class);
    }
    
}
