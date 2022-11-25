<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class acces_tokens extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'access_tokens';
   // protected $fillable = [ 'email', 'licenseplate', 'password'];
    public $timestamps=false;
}
