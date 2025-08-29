<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoUpload extends Model
{
    protected $fillable = ['po_no', 'supplier_name', 'file_path', 'file_url', 'status', 'n8n_response', 'user_id'];

    protected $casts = [
        'n8n_response' => 'array',
    ];
}
