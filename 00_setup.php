<?php

use App\ContentRepositoryStatusPrinter;

require __DIR__ . '/vendor/autoload.php';

$connection = App\Common::getConnection();

$contentRepositoryMaintainer = App\Common::getContentRepositoryMaintainer();

$contentRepositoryMaintainer->setUp();

echo "🟢 successfully setup database\n";

ContentRepositoryStatusPrinter::print($contentRepositoryMaintainer->status());
