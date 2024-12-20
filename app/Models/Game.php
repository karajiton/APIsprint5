<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'dice_one', 'dice_two', 'win'];

    public function player()
    {
        return $this->belongsTo(User::class);
    }
}

