<?php

use App\NodeTreePrinter;
use Neos\ContentGraph\DoctrineDbalAdapter\DoctrineDbalContentGraphProjectionFactory;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphProjection;
use Neos\ContentRepository\Core\Projection\ContentStream\ContentStreamProjection;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceProjection;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();

$userWorkspaceName = WorkspaceName::fromString('user-sebastian');
$userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($userWorkspaceName);
if ($userWorkspace) {
    echo sprintf("🟡  Workspace user-sebastian already existing\n");
    return;
}

$contentRepository->handle(
    CreateWorkspace::create(
        workspaceName: $userWorkspaceName,
        baseWorkspaceName: WorkspaceName::fromString('live'),
        workspaceTitle: WorkspaceTitle::fromString('Sebastian'),
        workspaceDescription: WorkspaceDescription::fromString('Sebastians IPC Workspace'),
        newContentStreamId: ContentStreamId::create()
    )
)->block();

echo sprintf("🟢 Created workspace\n");

NodeTreePrinter::print($contentRepository);
