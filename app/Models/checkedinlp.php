<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class checkedinlp extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table="checkedinlp";
    public $timestamps=false;
}
