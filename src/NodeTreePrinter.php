<?php

namespace App;

use Doctrine\DBAL\DriverManager;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Symfony\Component\Yaml\Yaml;

class NodeTreePrinter
{
    public static function print(ContentRepository $contentRepository): void
    {
        $i = 0;
        foreach ($contentRepository->getWorkspaceFinder()->findAll() as $workspace) {
            $i++;
            echo sprintf("📄 Workspace %s (Content Stream: %s):\n", $workspace->workspaceName->value, $workspace->currentContentStreamId->value);
            echo "\n";

            foreach ($contentRepository->getVariationGraph()->getDimensionSpacePoints() as $dimensionSpacePoint) {
                echo sprintf("   🏁 Dimension %s:\n", $dimensionSpacePoint->toJson());
                echo "\n";

                $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                    $workspace->currentContentStreamId,
                    $dimensionSpacePoint,
                    VisibilityConstraints::frontend()
                );

                self::printSubgraph($subgraph);
            }



            echo "\n\n";
        }

        if ($i === 0) {
            echo "‼️ No workspace found\n";
        }
    }

    private static function printSubgraph(ContentSubgraphInterface $subgraph): void
    {
        $rootNodeTypeName = NodeTypeName::fromString('MyProject:Root');
        $rootNode = $subgraph->findRootNodeByType($rootNodeTypeName);
        if (!$rootNode) {
            echo "      ❌ no root node found";
            return;
        }

        self::printChildNodes($rootNode, $subgraph, indentation: 6);
    }

    private static function printChildNodes(Node $node, ContentSubgraphInterface $subgraph, int $indentation): void
    {
        echo str_pad('', $indentation) . sprintf("%s (Type: %s) %s\n", $node->nodeAggregateId->value, $node->nodeTypeName->value, json_encode($node->properties->serialized()->getPlainValues()));

        foreach ($subgraph->findChildNodes($node->nodeAggregateId, FindChildNodesFilter::create()) as $childNode) {
            self::printChildNodes($childNode, $subgraph, $indentation + 3);
        }
    }
}
