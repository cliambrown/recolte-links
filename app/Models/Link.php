<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function tags() {
        return $this->belongsToMany(Tag::class, 'link_tags');
    }
    
    public function read_statuses() {
        return $this->hasMany(LinkReadStatus::class);
    }
    
    // This assumes that read_statuses has been loaded with ->where('user_id', auth()->user()->id)
    public function getUnreadAttribute() {
        return !$this->read_statuses->count();
    }
    
    public function likes() {
        return $this->hasMany(UserLikes::class);
    }
    
    public function getLikedAttribute() {
        return $this->likes->contains('user_id', auth()->user()->id);
    }

}
