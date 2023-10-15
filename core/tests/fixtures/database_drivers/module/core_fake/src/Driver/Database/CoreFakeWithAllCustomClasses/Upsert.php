<?php

namespace Drupal\core_fake\Driver\Database\CoreFakeWithAllCustomClasses;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * CoreFakeWithAllCustomClasses implementation of \Drupal\Core\Database\Upsert.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function execute() {}

  /**
   * {@inheritdoc}
   */
  public function __toString() {}

}
