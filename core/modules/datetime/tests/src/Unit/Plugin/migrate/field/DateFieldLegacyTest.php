<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field;

use Drupal\datetime\Plugin\migrate\field\DateField;
use Drupal\migrate\MigrateException;
use Drupal\Tests\UnitTestCase;

/**
 * Tests legacy methods on the date_field plugin.
 *
 * @group migrate
 * @group legacy
 */
class DateFieldLegacyTest extends UnitTestCase {

  /**
   * Tests deprecation on calling processFieldValues().
   *
   * @expectedDeprecation Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.
   */
  public function testUnknownDateType() {
    $migration = $this->prophesize('Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $plugin = new DateField([], '', []);

    $this->setExpectedException(MigrateException::class, "Field field_date of type 'timestamp' is an unknown date field type.");
    $plugin->processFieldValues($migration, 'field_date', ['type' => 'timestamp']);
  }

}
