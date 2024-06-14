<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamProjection;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();
$contentRepository->resetProjectionStates();

$options = CatchUpOptions::create(
    maximumSequenceNumber: $maximumSequenceNumber = 4
);
$contentRepository->catchUpProjection(ContentGraphProjection::class, $options);
$contentRepository->catchUpProjection(WorkspaceProjection::class, $options);
$contentRepository->catchUpProjection(ContentStreamProjection::class, $options);

echo sprintf("🟢 Replayed projection state up to sequence number %d\n", $maximumSequenceNumber);

NodeTreePrinter::print($contentRepository);

$contentRepository->resetProjectionStates();
$options = CatchUpOptions::create();
$contentRepository->catchUpProjection(ContentGraphProjection::class, $options);
$contentRepository->catchUpProjection(WorkspaceProjection::class, $options);
$contentRepository->catchUpProjection(ContentStreamProjection::class, $options);

echo sprintf("🟢 Replayed projection state up to NOW\n");

NodeTreePrinter::print($contentRepository);
