<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    public static function getTableData()
    {
        return self::all();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public $timestamps = false;
}
