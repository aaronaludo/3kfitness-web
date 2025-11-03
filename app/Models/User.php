<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function status(){
        return $this->belongsTo(Status::class);
    }

    public function role(){
        return $this->belongsTo(Role::class);
    }

    public function usermemberships(){
        return $this->hasMany(UserMembership::class);
    }
    
    public function memberships()
    {
        return $this->belongsToMany(Membership::class, 'user_memberships', 'user_id', 'membership_id');
    }

    public function trainerSchedules()
    {
        return $this->hasMany(Schedule::class, 'trainer_id');
    }
    
    public function userSchedules()
    {
        return $this->hasMany(UserSchedule::class);
    }
    
    public function payrolls(){
        return $this->hasMany(Payroll::class);
    }
}
