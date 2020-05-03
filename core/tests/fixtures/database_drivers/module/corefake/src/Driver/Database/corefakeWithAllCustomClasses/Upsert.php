<?php

namespace Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * CorefakeWithAllCustomClasses implementation of \Drupal\Core\Database\Upsert.
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
