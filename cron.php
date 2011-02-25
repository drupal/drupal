<?php

/**
 * @file
 * Handles incoming requests to fire off regularly-scheduled tasks (cron jobs).
 */

include_once 'includes/bootstrap.inc';
include_once 'includes/common.inc' ;

// If not in 'safe mode', increase the maximum execution time:
if (!ini_get('safe_mode')) {
  set_time_limit(240);
}

// Check if the last cron run completed
if (variable_get('cron_busy', false)) {
  watchdog('warning', t('Last cron run did not complete.'));
}
else {
  variable_set('cron_busy', true);
}

// Iterate through the modules calling their cron handlers (if any):
module_invoke_all('cron');

// Clean up
variable_set('cron_busy', false);
watchdog('regular', t('Cron run completed'));

?>
