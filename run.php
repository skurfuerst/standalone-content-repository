<?php

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

require __DIR__ . '/vendor/autoload.php';

$bootstrap = new \App\MinimalCrBootstrap();

$contentRepository = $bootstrap->buildContentRepository(ContentRepositoryId::fromString('default'));
