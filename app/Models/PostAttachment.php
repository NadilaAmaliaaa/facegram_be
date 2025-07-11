<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostAttachment extends Model
{
    protected $table = 'post_attachments';

    protected $fillable = [
        'storage_path', 'post_id'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
