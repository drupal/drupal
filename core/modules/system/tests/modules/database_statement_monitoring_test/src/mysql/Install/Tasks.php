<?php

namespace Drupal\database_statement_monitoring_test\mysql\Install;

use Drupal\mysql\Driver\Database\mysql\Install\Tasks as BaseTasks;

@trigger_error('\Drupal\database_statement_monitoring_test\mysql\Install\Tasks is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3318162', E_USER_DEPRECATED);

/**
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3318162
 */
class Tasks extends BaseTasks {
}
