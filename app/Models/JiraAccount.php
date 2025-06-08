<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JiraAccount extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'site_url', 'email', 'api_token'];

    protected $casts = [
        'api_token' => 'encrypted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
