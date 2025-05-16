<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    /**
     * Atrybuty, które można masowo przypisywać
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'message',
        'channel',
    ];

    /**
     * Atrybuty, które powinny być rzutowane
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}