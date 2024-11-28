<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,HasApiTokens,Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    //protected function getDefaultGuardName(): string { return 'api'; }
   
    //protected string $guard_name = 'api';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'success_rate',
        'registered_at'
    ];
    protected $dates = ['registered_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
