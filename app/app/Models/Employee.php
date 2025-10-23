<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'document', 'city', 'state', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
