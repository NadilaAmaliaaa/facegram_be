<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostAttachment extends Model
{
    use SoftDeletes;

    protected $table = 'post_attachments';

    protected $fillable = [
        'storage_path', 'post_id', 'deleted_at', 'updated_at', 'created_at'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
