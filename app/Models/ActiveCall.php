<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCall extends Model
{
    use HasFactory;

    protected array $fillable = ['inbound', 'outbound', 'domain'];
}
