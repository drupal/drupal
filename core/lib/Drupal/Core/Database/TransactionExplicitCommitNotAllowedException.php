<?php

namespace Drupal\Core\Database;

@trigger_error('Drupal\Core\Database\TransactionExplicitCommitNotAllowedException is deprecated in drupal:12.1.0 and is removed from drupal:13.0.0. There is no replacement. See https://www.drupal.org/node/3628686', E_USER_DEPRECATED);

/**
 * Exception to deny attempts to explicitly manage transactions.
 *
 * This exception will be thrown when the client connection commit() is called.
 * Code should never call this method directly.
 *
 * @deprecated in drupal:12.1.0 and is removed from drupal:13.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3628686
 */
class TransactionExplicitCommitNotAllowedException extends TransactionException {}
