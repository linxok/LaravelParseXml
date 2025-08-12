<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'xml_id',
        'parent_xml_id',
    ];

    // батьківська категорія
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_xml_id', 'xml_id');
    }

    // дочірні категорії
    public function children()
    {
        return $this->hasMany(self::class, 'parent_xml_id', 'xml_id');
    }
}
