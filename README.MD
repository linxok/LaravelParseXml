# LaravelParseXml — Каталог товарів з імпортом з XML

## Опис

Цей проект — бекенд для каталогу товарів з імпортом з XML, динамічними фільтрами,  REST API та простою фронтенд-сторінкою для демонстрації фільтрації та пагінації.

## Швидкий старт

1. **Запуск контейнерів**
```bash
sh restart.sh    # або sh stop.sh для зупинки
```
або напряму:
```bash
docker-compose up -d
```

2. **Міграції**
```bash
docker exec -it web bash
cd app
php artisan migrate
```

3. **Імпорт XML**
```bash
php artisan app:xml-parse test.xml
```

4. **Відкрити фронтенд**

    http://test.loc/catalog

## API

- **GET /api/catalog/products** — список товарів з фільтрацією, сортуванням, пагінацією
- **GET /api/catalog/filters** — список доступних фільтрів з динамічними count

### Параметри для /api/catalog/products
- `page` — номер сторінки
- `limit` — кількість на сторінці
- `sort_by` — price_asc, price_desc, id_asc
- `filter[parameter_slug][]=value` — фільтрація по параметрах
- `filter[category_xml_id][]=123` — фільтрація по категорії

### Відповідь (приклад)
```json
{
  "data": [
    {
      "id": 1,
      "name": "Ноутбук X1",
      "price": 25000,
      "description": "Потужний ноутбук...",
      "available": true,
      "category": "Ноутбуки",
      "pictures": ["https://..."],
      "parameters": [
        {"name": "Бренд", "value": "Lenovo"},
        {"name": "Колір", "value": "Чорний"}
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

## Структура БД (основне)
- products (товари)
- categories (категорії)
- parameters (словник параметрів)
- parameter_values (словник значень)
- product_parameter_value (зв'язок товар-параметр)

