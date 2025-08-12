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

        $query = Product::query();

        // накладаємо фільтри через whereHas
        foreach ($filters as $slug => $values) {
            $values = (array) $values;
            $query->whereHas('parameterValues.parameter', function($q) use($slug) {
                $q->where('slug', $slug);
            })->whereHas('parameterValues', function($q) use($values) {
                $q->whereIn('value', $values);
            });
        }

        // сортування
        if ($sortBy === 'price_asc')        { $query->orderBy('price','asc'); }
        else if ($sortBy === 'price_desc') { $query->orderBy('price','desc'); }
        else                                 { $query->orderBy('id','asc');    }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ]);
    }
}
