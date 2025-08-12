<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $page     = (int)$request->query('page', 1);
        $limit    = (int)$request->query('limit', 10);
        $sortBy   = $request->query('sort_by');
        $filters  = $request->query('filter', []);

        $query = Product::query()->with(['parameterValues.parameter', 'category']);

        // Фільтри по параметрах і категорії
        foreach ($filters as $slug => $values) {
            $values = (array) $values;
            if ($slug === 'category_xml_id') {
                $query->whereIn('category_xml_id', $values);
                continue;
            }
            $query->whereHas('parameterValues.parameter', function($q) use($slug) {
                $q->where('slug', $slug);
            })->whereHas('parameterValues', function($q) use($values) {
                $q->whereIn('value', $values);
            });
        }

        // Сортування
        if ($sortBy === 'price_asc')        { $query->orderBy('price','asc'); }
        else if ($sortBy === 'price_desc') { $query->orderBy('price','desc'); }
        else                                 { $query->orderBy('id','asc');    }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $products = $paginator->getCollection()->map(function($product) {
            // Параметри
            $parameters = [];
            foreach ($product->parameterValues as $pv) {
                if ($pv->parameter && $pv->value) {
                    $parameters[] = [
                        'name' => $pv->parameter->name,
                        'value' => $pv->value,
                    ];
                }
            }
            // Фото
            $pictures = $product->pictures;
            if (is_string($pictures)) {
                $decoded = json_decode($pictures, true);
                $pictures = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($pictures)) $pictures = [];
            // Категорія
            $categoryTitle = $product->category ? $product->category->title : null;

            return [
                'id' => $product->id,
                'xml_id' => $product->xml_id,
                'name' => $product->name,
                'price' => $product->price,
                'description' => $product->description,
                'available' => $product->available,
                'category_xml_id' => $product->category_xml_id,
                'category' => $categoryTitle,
                'currency' => $product->currency,
                'stock_quantity' => $product->stock_quantity,
                'description_format' => $product->description_format,
                'vendor' => $product->vendor,
                'vendor_code' => $product->vendor_code,
                'barcode' => $product->barcode,
                'pictures' => $pictures,
                'parameters' => $parameters,
            ];
        })->values();

        return response()->json([
            'data' => $products,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }
}
