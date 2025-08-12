<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['xml_id','name','price','description'];

    public function parameterValues()
    {
        return $this->belongsToMany(
            ParameterValue::class,
            'product_parameter_value',
            'product_id',
            'parameter_value_id'
        );
    }
}
