<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CatalogImportService;

class XmlParse extends Command
{
    protected $signature   = 'app:xml-parse {file=storage/app/data.xml}';
    protected $description = 'Імпорт каталогу товарів із XML';

    public function handle()
    {
        $path = $this->argument('file');
        $this->info("Старт імпорту з файлу: {$path}");
        (new CatalogImportService($path))->import();
        $this->info("Імпорт завершено.");
        return 0;
    }
}
