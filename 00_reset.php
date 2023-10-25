<?php

require __DIR__ . '/vendor/autoload.php';

$connection = App\Common::getConnection();
$contentRepository = App\Common::getContentRepository();

$connection->executeStatement('TRUNCATE cr_default_events');
$contentRepository->resetProjectionStates();

echo "🟢 successfully reset database\n";
