<?php

use App\ContentRepositoryStatusPrinter;
use App\NodeTreePrinter;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();
$contentRepositoryMaintainer = App\Common::getContentRepositoryMaintainer();

$contentRepositoryMaintainer->replayAllSubscriptions();

echo "🟢 Replayed projection states\n";

NodeTreePrinter::print($contentRepository);

ContentRepositoryStatusPrinter::print($contentRepositoryMaintainer->status());
