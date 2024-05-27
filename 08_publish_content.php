<?php

use App\NodeTreePrinter;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

require __DIR__ . '/vendor/autoload.php';

$contentRepository = App\Common::getContentRepository();

$userWorkspaceName = WorkspaceName::fromString('user-sebastian');

$contentRepository->handle(
    PublishWorkspace::create(
        $userWorkspaceName
    )
);

echo sprintf("🟢 Published content from user-sebastian -> live\n");

NodeTreePrinter::print($contentRepository);
