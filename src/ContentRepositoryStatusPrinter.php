<?php

declare(strict_types=1);

namespace App;

use Neos\ContentRepository\Core\Projection\ProjectionStatusType;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryStatus;
use Neos\ContentRepository\Core\Subscription\DetachedSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\ProjectionSubscriptionStatus;
use Neos\ContentRepository\Core\Subscription\SubscriptionStatus;
use Neos\EventStore\Model\EventStore\StatusType;

class ContentRepositoryStatusPrinter
{
    private const STYLE_BRIGHT = 1;
    private const STYLE_ERROR = 31;
    private const STYLE_SUCCESS = 32;
    private const STYLE_COMMENT = 33;

    public static function print(ContentRepositoryStatus $contentRepositoryStatus): bool
    {
        $hasErrors = false;
        self::outputLine('Event Store:');
        self::output('  Setup: ');
        self::outputLine(match ($contentRepositoryStatus->eventStoreStatus->type) {
            StatusType::OK => '<success>OK</success>',
            StatusType::SETUP_REQUIRED => '<comment>Setup required!</comment>',
            StatusType::ERROR => '<error>ERROR</error>',
        });
        if ($contentRepositoryStatus->eventStorePosition) {
            self::outputLine('  Position: %d', [$contentRepositoryStatus->eventStorePosition->value]);
        } else {
            self::outputLine('  Position: <error>Loading failed!</error>');
        }
        $hasErrors |= $contentRepositoryStatus->eventStoreStatus->type === StatusType::ERROR;
        if ($contentRepositoryStatus->eventStoreStatus->details !== '') {
            self::outputWithLeftPadding($contentRepositoryStatus->eventStoreStatus->details);
        }
        self::outputLine();
        self::outputLine('Subscriptions:');

        if ($contentRepositoryStatus->subscriptionStatus->isEmpty()) {
            self::outputLine('<error>There are no registered subscriptions yet, please setup</error>');
            return false;
        }
        foreach ($contentRepositoryStatus->subscriptionStatus as $status) {
            if ($status instanceof DetachedSubscriptionStatus) {
                self::outputLine('  <b>%s</b>:', [$status->subscriptionId->value]);
                self::output('    Subscription: ');
                self::output('%s <comment>DETACHED</comment>', [$status->subscriptionId->value, $status->subscriptionStatus === SubscriptionStatus::DETACHED ? 'is' : 'will be']);
                self::outputLine(' at position <b>%d</b>', [$status->subscriptionPosition->value]);
            }
            if ($status instanceof ProjectionSubscriptionStatus) {
                self::outputLine('  <b>%s</b>:', [$status->subscriptionId->value]);
                self::output('    Setup: ');
                self::outputLine(match ($status->setupStatus->type) {
                    ProjectionStatusType::OK => '<success>OK</success>',
                    ProjectionStatusType::SETUP_REQUIRED => '<comment>SETUP REQUIRED</comment>',
                    ProjectionStatusType::ERROR => '<error>ERROR</error>',
                });
                $hasErrors |= $status->setupStatus->type === ProjectionStatusType::ERROR;
                if ($status->setupStatus->type !== ProjectionStatusType::OK) {
                    $lines = explode(chr(10), $status->setupStatus->details ?: '<comment>No details available.</comment>');
                    foreach ($lines as $line) {
                        self::outputLine('      ' . $line);
                    }
                    self::outputLine();
                }
                self::output('    Projection: ');
                self::output(match ($status->subscriptionStatus) {
                    SubscriptionStatus::NEW => '<comment>NEW</comment>',
                    SubscriptionStatus::BOOTING => '<comment>BOOTING</comment>',
                    SubscriptionStatus::ACTIVE => '<success>ACTIVE</success>',
                    SubscriptionStatus::DETACHED => '<comment>DETACHED</comment>',
                    SubscriptionStatus::ERROR => '<error>ERROR</error>',
                });
                if ($contentRepositoryStatus->eventStorePosition?->value > $status->subscriptionPosition->value) {
                    // projection is behind
                    self::outputLine(' at position <error>%d</error>', [$status->subscriptionPosition->value]);
                } else {
                    self::outputLine(' at position <b>%d</b>', [$status->subscriptionPosition->value]);
                }
                $hasErrors |= $status->subscriptionStatus === SubscriptionStatus::ERROR;
                if ($status->subscriptionError !== null) {
                    $lines = explode(chr(10), $status->subscriptionError->errorMessage ?: '<comment>No details available.</comment>');
                    foreach ($lines as $line) {
                        self::outputLine('<error>      %s</error>', [$line]);
                    }
                }
            }
        }
        return !$hasErrors;
    }

    /**
     * @param array<mixed> $params
     */
    private static function output(string $content, array $params = []): void
    {
        $content = preg_replace('|\<b>(((?!\</b>).)*)\</b>|', "\x1B[" . self::STYLE_BRIGHT . "m\$1\x1B[0m", $content) ?? $content;
        $content = preg_replace('|\<error>(((?!\</error>).)*)\</error>|', "\x1B[" . self::STYLE_ERROR . "m\$1\x1B[0m", $content) ?? $content;
        $content = preg_replace('|\<comment>(((?!\</comment>).)*)\</comment>|', "\x1B[" . self::STYLE_COMMENT . "m\$1\x1B[0m", $content) ?? $content;
        $content = preg_replace('|\<success>(((?!\</success>).)*)\</success>|', "\x1B[" . self::STYLE_SUCCESS . "m\$1\x1B[0m", $content) ?? $content;
        echo vsprintf($content, $params);
    }

    /**
     * @param array<mixed> $params
     */
    private static function outputLine(string $content = '', array $params = []): void
    {
        self::output($content, $params);
        echo "\n";
    }

    private static function outputWithLeftPadding(string $content): void
    {
        self::output('  ' . str_replace("\n", "\n  ", $content));
    }
}
