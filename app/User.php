<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;

    public $timestamps = false;
    protected $table = 'users_account';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        $access = array();

        if($this->cposms_access)
            $access['cposms'] = true;

        if($this->pjoms_access)
            $access['pjoms'] = true;

        return ['department' => $this->department, 'username' => $this->username,'type' => $this->type, 'access' => $access];
    }

    public function cposmsLogs()
    {
        return $this->hasMany('App\UserLogs','user_id');
    }

    public function pjomsLogs()
    {
        return $this->hasMany('App\UserLogs','user_id');
    }
}
