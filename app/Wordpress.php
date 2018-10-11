<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Wordpress extends Model
{
    protected $connection = 'mysql2';

    protected $table = 'wp_users';

    protected $fillable = ['name', 'username', 'email'];
}
