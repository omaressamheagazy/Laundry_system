<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageServices extends Model
{
    use HasFactory;
    public function packages() {
        return $this->belongsTo(Package::class,'package_id');
    }
}
