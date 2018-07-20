<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field\d6;

use Drupal\datetime\Plugin\migrate\field\d6\DateField;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group migrate
 * @group legacy
 */
class DateFieldTest extends UnitTestCase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migration = $this->prophesize(MigrationInterface::class)->reveal();
  }

  /**
   * Tests an Exception is thrown when the field type is not a known date type.
   *
   * @runInSeparateProcess
   * @expectedDeprecation DateField is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\datetime\Plugin\migrate\field\DateField instead.
   */
  public function testUnknownDateType($method = 'defineValueProcessPipeline') {
    $plugin = new DateField([], '', []);

    $this->setExpectedException(MigrateException::class, "Field field_date of type 'timestamp' is an unknown date field type.");
    $plugin->$method($this->migration, 'field_date', ['type' => 'timestamp']);
  }

}
