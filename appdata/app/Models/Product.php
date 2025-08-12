<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'xml_id',
        'name',
        'price',
        'description',
        'available',
        'category_xml_id',
        'currency',
        'stock_quantity',
        'description_format',
        'vendor',
        'vendor_code',
        'barcode',
        'pictures',
    ];

    protected $casts = [
        'available' => 'boolean',
        'pictures' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_xml_id', 'xml_id');
    }

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
