<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MmomUser extends Authenticatable
{
    use HasUuids, Notifiable;
    protected $table = 'identity.users';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['display_name','email','password_hash','is_active'];
    protected $hidden = ['password_hash','remember_token','two_factor_secret','two_factor_recovery_codes'];
    protected $casts = ['is_active'=>'boolean','email_verified_at'=>'datetime','two_factor_confirmed_at'=>'datetime'];
    public function getAuthPasswordName(): string { return 'password_hash'; }
    public function getAuthPassword(): string { return (string) $this->password_hash; }
    public function getNameAttribute(): string { return $this->display_name; }
}
