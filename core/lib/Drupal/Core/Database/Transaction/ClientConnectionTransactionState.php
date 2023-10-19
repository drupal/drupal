<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

/**
 * Enumeration of the possible states of a client connection transaction.
 */
enum ClientConnectionTransactionState {

  case Active;
  case RolledBack;
  case RollbackFailed;
  case Committed;
  case CommitFailed;

  // In some cases the active transaction can be automatically committed by
  // the database server (for example, MySql when a DDL statement is executed
  // during a transaction). We track such cases with 'Voided' when we can
  // detect them.
  case Voided;

}
