<?php
require_once __DIR__ . '/countdown_config.php';

/**
 * Compute the status_deadline to store for a given new property status.
 * Returns null for statuses that don't carry a countdown.
 */
function computeStatusDeadline(string $newStatus): ?string
{
    $days = match ($newStatus) {
        'under_offer' => UNDER_OFFER_PERIOD_DAYS,
        'sold' => SOLD_PERIOD_DAYS,
        default => null,
    };

    return $days !== null
        ? (new DateTime())->modify("+{$days} days")->format('Y-m-d H:i:s')
        : null;
}

/**
 * Lazy expiry sweep: flips any overdue under_offer/sold property to 'expired'.
 * There is no cron in this app, so this substitutes for one — cheap, indexed,
 * idempotent, safe to call once per request from pages that show property status.
 */
function expireOverdueProperties(PDO $pdo): void
{
    static $ranThisRequest = false;
    if ($ranThisRequest) {
        return;
    }
    $ranThisRequest = true;

    $pdo->exec("
        UPDATE seller_properties
        SET property_status = 'expired', status_deadline = NULL
        WHERE property_status IN ('under_offer','sold')
          AND status_deadline IS NOT NULL
          AND status_deadline < NOW()
    ");
}
