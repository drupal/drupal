<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field;

use Drupal\datetime\Plugin\migrate\field\DateField;
use Drupal\migrate\MigrateException;
use Drupal\Tests\UnitTestCase;

/**
 * @group migrate
 */
class DateFieldTest extends UnitTestCase {

  /**
   * Tests an Exception is thrown when the field type is not a known date type.
   */
  public function testUnknownDateType($method = 'defineValueProcessPipeline') {
    $migration = $this->prophesize('Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $plugin = new DateField([], '', []);

    $this->setExpectedException(MigrateException::class, "Field field_date of type 'timestamp' is an unknown date field type.");
    $plugin->$method($migration, 'field_date', ['type' => 'timestamp']);
  }

}
