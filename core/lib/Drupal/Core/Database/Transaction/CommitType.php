<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

/**
 * Enumeration of the types of commit for Drupal transactions.
 */
enum CommitType {

  case Explicit;
  case ImplicitOnDestruct;
  case AutomaticUponDDL;

}
