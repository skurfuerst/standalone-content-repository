<?php

use App\NodeTreePrinter;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();
$contentRepository->resetProjectionStates();

echo "🟢 Reset projection state\n";

NodeTreePrinter::print($contentRepository);
