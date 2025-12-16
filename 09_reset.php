<?php

use App\ContentRepositoryStatusPrinter;

require __DIR__ . '/vendor/autoload.php';

$connection = App\Common::getConnection();

$contentRepositoryMaintainer = App\Common::getContentRepositoryMaintainer();

$contentRepositoryMaintainer->prune();

echo "🟢 successfully reset database\n";

ContentRepositoryStatusPrinter::print($contentRepositoryMaintainer->status());
