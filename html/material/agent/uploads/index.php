<?php
/**
 * Directory Access Protection
 * Prevents direct access to uploaded files directory
 */

// Redirect to portal home if accessed directly
header('Location: ../../');
exit;
