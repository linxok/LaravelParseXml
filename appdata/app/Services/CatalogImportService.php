<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\ParameterValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use XMLReader;

class CatalogImportService
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Основний запуск імпорту:
     * 1) Парсинг категорій
     * 2) Парсинг товарів (offer) через XMLReader
     */
    public function import(): void
    {
        // 1) Імпортуємо категорії
        $this->parseCategories();

        // 2) Імпортуємо товари
        $reader = new XMLReader();
        $reader->open($this->filePath);


        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'offer') {
                $simpleXml = simplexml_load_string($reader->readOuterXML());
                $this->processOffer($simpleXml);
            }
        }
        $reader->close();
    }

    /**
     * Парсинг категорій з секції <shop><categories>
     */
    protected function parseCategories(): void
    {
        $xml = simplexml_load_file($this->filePath);

        foreach ($xml->shop->categories->category as $cat) {
            $xmlId = (int)$cat['id'];
            $parentId = isset($cat['parentId']) ? (int)$cat['parentId'] : null;
            $title = trim((string)$cat);

            Category::updateOrCreate(
                ['xml_id' => $xmlId],
                [
                    'title' => $title,
                    'parent_xml_id' => $parentId,
                ]
            );
        }
    }

    /**
     * Обробка одного <offer>
     */
    protected function processOffer(\SimpleXMLElement $offer): void
    {
        // Валідація базових полів
        $xmlId = (string)$offer['id'];
        $name = trim((string)$offer->name);
        $price = (float)$offer->price;

        if (empty($xmlId) || empty($name) || $price <= 0) {
            return;
        }

        // Зв’язок з категорією (якщо є)
        $category = null;
        if (isset($offer->categoryId)) {
            $categoryXmlId = (int)$offer->categoryId;
            $category = Category::firstWhere('xml_id', $categoryXmlId);
        }

        // Створюємо або оновлюємо продукт
        $product = Product::updateOrCreate(
            ['xml_id' => $xmlId],
            [
                'name' => $name,
                'price' => $price,
                'description' => (string)$offer->description,
                'category_id' => $category?->id,  // PHP 8 null-safe
            ]
        );

        // Очищаємо старі значення параметрів
        $product->parameterValues()->detach();

        // Обробляємо динамічні параметри <param name="…">value</param>
        foreach ($offer->param as $param) {
            $paramName = trim((string)$param['name']);
            $paramValue = trim((string)$param);

            if ($paramName === '' || $paramValue === '') {
                continue;
            }

            // Створюємо slug із назви параметра
            $slug = Str::slug($paramName, '_');

            // 1) Параметр (filter)
            $parameter = Parameter::firstOrCreate(
                ['slug' => $slug],
                ['name' => $paramName]
            );

            // 2) Значення параметра
            $value = ParameterValue::firstOrCreate(
                [
                    'parameter_id' => $parameter->id,
                    'value' => $paramValue,
                ]
            );

            // 3) Pivot product–parameter_value
            $product->parameterValues()->attach($value->id);

            // 4) Оновлюємо Redis-множини для фільтрів
            $this->updateRedisSets($slug, $paramValue, $product->id);
        }
    }

    /**
     * Додає ID продукту до Redis-set-ів:
     * - filter:{slug}:{value}     — множина продуктів з цим значенням
     * - filter:{slug}:__values__  — список всіх існуючих значень параметра
     */
    protected function updateRedisSets(string $paramSlug, string $paramValue, int $productId): void
    {
        $redis = Redis::connection();

        $keyValueSet = "filter:{$paramSlug}:{$paramValue}";
        $allValuesKey = "filter:{$paramSlug}:__values__";

        $redis->sadd($keyValueSet, $productId);
        $redis->sadd($allValuesKey, $paramValue);
    }
}
