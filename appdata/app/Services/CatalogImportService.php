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
        echo "[INFO] Старт парсингу категорій...\n";
        $catCount = $this->parseCategories();
        echo "[INFO] Категорій оброблено: {$catCount}\n";

        echo "[INFO] Старт парсингу товарів...\n";
        $reader = new XMLReader();
        $reader->open($this->filePath);
        $offerCount = 0;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'offer') {
                $simpleXml = simplexml_load_string($reader->readOuterXML());
                $this->processOffer($simpleXml);
                $offerCount++;
                if ($offerCount % 100 === 0) {
                    echo "[INFO] Оброблено товарів: {$offerCount}\n";
                }
            }
        }
        $reader->close();
        echo "[INFO] Парсинг завершено. Всього товарів: {$offerCount}\n";
    }

    /**
     * Парсинг категорій з секції <shop><categories>
     */
    protected function parseCategories(): int
    {
        $xml = simplexml_load_file($this->filePath);
        $count = 0;
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
            $count++;
        }
        return $count;
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

        // Додаткові поля з offer
        $available = isset($offer['available']) ? ((string)$offer['available'] === 'true' ? 1 : 0) : 1;
        $categoryXmlId = isset($offer->categoryId) ? (int)$offer->categoryId : null;
        $currency = isset($offer->currencyId) ? (string)$offer->currencyId : null;
        $stockQuantity = isset($offer->stock_quantity) ? (int)$offer->stock_quantity : null;
        $descriptionFormat = isset($offer->description_format) ? (string)$offer->description_format : null;
        $vendor = isset($offer->vendor) ? (string)$offer->vendor : null;
        $vendorCode = isset($offer->vendorCode) ? (string)$offer->vendorCode : null;
        $barcode = isset($offer->barcode) ? (string)$offer->barcode : null;
        // Збір картинок у масив
        $pictures = [];
        if (isset($offer->picture)) {
            foreach ($offer->picture as $pic) {
                $pictures[] = (string)$pic;
            }
        }

        // Створюємо або оновлюємо продукт
        $product = Product::updateOrCreate(
            ['xml_id' => $xmlId],
            [
                'name' => $name,
                'price' => $price,
                'description' => (string)$offer->description,
                'available' => $available,
                'category_xml_id' => $categoryXmlId,
                'currency' => $currency,
                'stock_quantity' => $stockQuantity,
                'description_format' => $descriptionFormat,
                'vendor' => $vendor,
                'vendor_code' => $vendorCode,
                'barcode' => $barcode,
                'pictures' => !empty($pictures) ? json_encode($pictures, JSON_UNESCAPED_UNICODE) : null,
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
            // $this->updateRedisSets($slug, $paramValue, $product->id); // <--- Redis тимчасово вимкнено
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
