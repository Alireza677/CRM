<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsBlacklist extends Model
{
    public $timestamps = true;
    protected $table = 'sms_blacklists';
    protected $fillable = ['mobile', 'note'];
}

