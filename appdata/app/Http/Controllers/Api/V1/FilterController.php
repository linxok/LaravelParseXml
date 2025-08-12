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
        $redis = Redis::connection();

        // 1) Формуємо базовий список ID для активних фільтрів
        $baseKeys = [];
        foreach ($activeFilters as $slug => $values) {
            foreach ((array)$values as $val) {
                $baseKeys[] = "filter:{$slug}:{$val}";
            }
        }
        // якщо є хоч один активний — перетинаємо, інакше нехай базовий буде null
        $baseSet = null;
        if (count($baseKeys)) {
            $tempKey = 'filter:temp:base';
            // зберігаємо результат перетину в тимчасовий сет
            $redis->sinterstore($tempKey, $baseKeys);
            $baseSet = $tempKey;
        }

        // 2) Для кожного параметра збираємо можливі значення
        $parameters = Parameter::all();
        $result = [];

        foreach ($parameters as $param) {
            $values = [];
            // список усіх значень береcя з Redis множини
            $allVals = $redis->smembers("filter:{$param->slug}:__values__");
            foreach ($allVals as $val) {
                $key = "filter:{$param->slug}:{$val}";

                // визначаємо активність
                $isActive = isset($activeFilters[$param->slug]) &&
                    in_array($val, (array)$activeFilters[$param->slug]);

                // рахуємо перетин
                if ($baseSet) {
                    // додаємо до перетину ще цей сет
                    $count = $redis->sinterstore('filter:temp:calc', [$baseSet, $key]);
                    // видаляємо тимчасовий
                    $redis->del('filter:temp:calc');
                } else {
                    // без активних фільтрів — просто розмір множини
                    $count = $redis->scard($key);
                }

                $values[] = [
                    'value'  => $val,
                    'count'  => (int)$count,
                    'active' => (bool)$isActive,
                ];
            }

            // прибираємо тимчасовий базовий сет
            if ($baseSet) {
                $redis->del($baseSet);
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
