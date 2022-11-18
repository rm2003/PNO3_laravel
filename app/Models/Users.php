<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Users extends Model
{
    //use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;
   // protected $fillable = [ 'email', 'licenseplate', 'password'];
    public $timestamps=false;
}
