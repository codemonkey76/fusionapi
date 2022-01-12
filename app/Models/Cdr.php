<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'v_xml_cdr';
    protected $hidden = ['json'];
}
