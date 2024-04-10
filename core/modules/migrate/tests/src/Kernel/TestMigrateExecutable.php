<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateExecutable;

/**
 * Tests MigrateExecutable.
 */
class TestMigrateExecutable extends MigrateExecutable {

  /**
   * {@inheritdoc}
   */
  protected function getIdMap() {
    // This adds test coverage that this works.
    return new TestFilterIterator(parent::getIdMap());
  }

  /**
   * {@inheritdoc}
   */
  protected function getSource() {
    // This adds test coverage that this works.
    return new TestFilterIterator(parent::getSource());
  }

}
