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
        'password',
        'deleted_at'
    ];

    protected $guarded = [
        'id'
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

        if($this->pmms_access)
            $access['pmms'] = true;

        if($this->cims_access)
            $access['cims'] = true;

        if($this->wims_access)
            $access['wims'] = true;

        if($this->psms_access)
            $access['psms'] = true;

        if($this->salesms_access)
            $access['salesms'] = true;


        return ['department' => $this->department, 'username' => $this->username,'type' => $this->type, 'access' => $access];
    }

    public function pr()
    {
      return $this->hasMany('App\PurchaseRequest','pr_user_id');
    }

    public function prsd()
    {
      return $this->hasMany('App\PurchaseRequest','prsd_user_id');
    }

    public function pra()
    {
      return $this->hasMany('App\PurchaseRequestApproval','pra_approver_id');
    }

    public function pos()
    {
      return $this->hasMany('App\PurchaseOrderSupplier','spo_user_id');
    }

    public function cposmsLogs()
    {
        return $this->hasMany('App\CposmsLogs','user_id');
    }

    public function pjomsLogs()
    {
        return $this->hasMany('App\PjomsLogs','user_id');
    }

    public function pmmsLogs()
    {
        return $this->hasMany('App\PmmsLogs','user_id');
    }

    public function wimsLogs()
    {
        return $this->hasMany('App\WimsLogs','user_id');
    }

    public function prmsLogs()
    {
        return $this->hasMany('App\PrmsLogs','user_id');
    }

    public function psmsLogs()
    {
        return $this->hasMany('App\PsmsLogs','user_id');
    }

}
