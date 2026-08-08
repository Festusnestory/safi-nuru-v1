<?php
/**
 * Backward-compatible buyer submission route.
 *
 * The public form posts to submit.php. Keep this entry point for older
 * browser tabs and integrations that still use the original URL.
 */
require __DIR__ . '/submit.php';
