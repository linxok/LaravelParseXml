<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class FilterController extends Controller
{
    public function index(Request $request)
    {
        $activeFilters = $request->query('filter', []);

        // Категорії як окремий фільтр (без Redis)
        $categoryFilter = [];
        $categories = \App\Models\Category::orderBy('title')->get();
        $catValues = [];
        foreach ($categories as $cat) {
            // Рахуємо кількість товарів у категорії з урахуванням інших активних фільтрів
            $query = \App\Models\Product::query()->where('category_xml_id', $cat->xml_id);
            // Додаємо інші фільтри (крім категорії)
            foreach ($activeFilters as $slug => $values) {
                if ($slug === 'category_xml_id') continue;
                $query->whereHas('parameterValues.parameter', function($q) use ($slug) {
                    $q->where('slug', $slug);
                }, '>=', 1);
                $query->whereHas('parameterValues', function($q) use ($slug, $values) {
                    $q->whereIn('value', (array)$values);
                });
            }
            $count = $query->count();
            $isActive = isset($activeFilters['category_xml_id']) && in_array($cat->xml_id, (array)$activeFilters['category_xml_id']);
            $catValues[] = [
                'value' => $cat->xml_id,
                'label' => $cat->title,
                'count' => $count,
                'active' => $isActive,
            ];
        }
        $categoryFilter = [
            'name' => 'Категорія',
            'slug' => 'category_xml_id',
            'values' => array_map(function($v) {
                return [
                    'value' => $v['value'],
                    'count' => $v['count'],
                    'active' => $v['active'],
                    'label' => $v['label'],
                ];
            }, $catValues),
        ];

        // 2) Для кожного параметра збираємо можливі значення (без Redis)
        $parameters = Parameter::all();
        $result = [$categoryFilter];

        foreach ($parameters as $param) {
            $values = [];
            // список усіх значень беремо з parameter_values
            $allVals = \App\Models\ParameterValue::where('parameter_id', $param->id)->pluck('value');
            foreach ($allVals as $val) {
                // визначаємо активність
                $isActive = isset($activeFilters[$param->slug]) && in_array($val, (array)$activeFilters[$param->slug]);

                // рахуємо кількість товарів з цим значенням (з урахуванням інших фільтрів)
                $query = \App\Models\Product::query();
                // Категорія
                if (isset($activeFilters['category_xml_id'])) {
                    $query->whereIn('category_xml_id', (array)$activeFilters['category_xml_id']);
                }
                // Інші параметри
                foreach ($activeFilters as $slug => $values2) {
                    if ($slug === $param->slug) continue;
                    if ($slug === 'category_xml_id') continue;
                    $query->whereHas('parameterValues.parameter', function($q) use ($slug) {
                        $q->where('slug', $slug);
                    }, '>=', 1);
                    $query->whereHas('parameterValues', function($q) use ($slug, $values2) {
                        $q->whereIn('value', (array)$values2);
                    });
                }
                // Поточний параметр
                $query->whereHas('parameterValues', function($q) use ($param, $val) {
                    $q->where('parameter_id', $param->id)->where('value', $val);
                });
                $count = $query->count();

                $values[] = [
                    'value'  => $val,
                    'count'  => (int)$count,
                    'active' => (bool)$isActive,
                ];
            }

            $result[] = [
                'name'   => $param->name,
                'slug'   => $param->slug,
                'values' => $values,
            ];
        }

        return response()->json($result);
    }
}
