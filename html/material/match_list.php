<?php
declare(strict_types=1);
// Legacy note: this file was a chrome-less fragment only ever reached via
// include("match_list.php") from match-table.php (never linked to or
// requested directly anywhere in the app - confirmed by grep). It had no
// session/role guard of its own and relied entirely on match-table.php
// having already run requireRole() and matching-functions1.php before
// including it; requested standalone it would fatal on the undefined
// roleDisplayName() call. Now that match-table.php is itself a shim calling
// MatchingController::table(), there is nothing left for this file to be
// included by, so it delegates to the same controller method match-table.php
// used (agent chrome) rather than reproducing that fatal error.
require_once __DIR__ . '/../../app/autoload.php';
(new \App\Controllers\Admin\MatchingController())->table('agent');
