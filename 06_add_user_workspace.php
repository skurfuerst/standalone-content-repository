<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
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
