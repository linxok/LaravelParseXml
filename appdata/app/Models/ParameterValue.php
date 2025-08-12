<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParameterValue extends Model
{
    use HasFactory;

    protected $fillable = ['parameter_id','value'];

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_parameter_value',
            'parameter_value_id',
            'product_id'
        );
    }
}
