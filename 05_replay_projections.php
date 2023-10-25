<?php

use App\NodeTreePrinter;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamProjection;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();
$contentRepository->resetProjectionStates();

$options = CatchUpOptions::create(
    maximumSequenceNumber: 4
);
$contentRepository->catchUpProjection(ContentGraphProjection::class, $options);
$contentRepository->catchUpProjection(WorkspaceProjection::class, $options);
$contentRepository->catchUpProjection(ContentStreamProjection::class, $options);

echo sprintf("🟢 Replayed projection state up to sequence number %d\n", $options->maximumSequenceNumber->value);

NodeTreePrinter::print($contentRepository);

$contentRepository->resetProjectionStates();
$options = CatchUpOptions::create();
$contentRepository->catchUpProjection(ContentGraphProjection::class, $options);
$contentRepository->catchUpProjection(WorkspaceProjection::class, $options);
$contentRepository->catchUpProjection(ContentStreamProjection::class, $options);

echo sprintf("🟢 Replayed projection state up to NOW\n");

NodeTreePrinter::print($contentRepository);
