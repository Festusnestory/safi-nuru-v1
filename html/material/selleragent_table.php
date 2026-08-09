<?php
declare(strict_types=1);
// Legacy guard preserved as-is: this fragment was only ever reachable via
// agentsellers_matched.php's NURU_SELLER_AGENT_INCLUDE-gated include, never
// as a direct request. The consolidated content now lives in
// MatchingController::sellerPortfolio() (routed at /admin/agentsellers-matched).
http_response_code(404);
exit('Not found.');
