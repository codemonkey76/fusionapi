<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'v_xml_cdr';
}
