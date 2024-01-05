<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\TimeZoneFormHelper
 * @group Datetime
 */
class TimeZoneFormHelperTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getOptionsList
   * @covers ::getOptionsListByRegion
   */
  public function testGetList(): void {
    // Test the default parameters for getOptionsList().
    $result = TimeZoneFormHelper::getOptionsList();
    $this->assertIsArray($result);
    $this->assertArrayHasKey('Africa/Dar_es_Salaam', $result);
    $this->assertEquals('Africa/Dar es Salaam', $result['Africa/Dar_es_Salaam']);

    // Test that the ungrouped and grouped results have the same number of
    // items.
    $ungrouped_count = count(TimeZoneFormHelper::getOptionsList());
    $grouped_result = TimeZoneFormHelper::getOptionsListByRegion();
    $grouped_count = 0;
    array_walk_recursive($grouped_result, function () use (&$grouped_count) {
      $grouped_count++;
    });
    $this->assertEquals($ungrouped_count, $grouped_count);
  }

  /**
   * @covers ::getOptionsListByRegion
   */
  public function testGetGroupedList(): void {
    // Tests time zone grouping.
    $result = TimeZoneFormHelper::getOptionsListByRegion();

    // Check a two-level time zone.
    $this->assertIsArray($result);
    $this->assertArrayHasKey('Africa', $result);
    $this->assertArrayHasKey('Africa/Dar_es_Salaam', $result['Africa']);
    $this->assertEquals('Dar es Salaam', $result['Africa']['Africa/Dar_es_Salaam']);

    // Check a three level time zone.
    $this->assertArrayHasKey('America', $result);
    $this->assertArrayHasKey('America/Indiana/Indianapolis', $result['America']);
    $this->assertEquals('Indianapolis (Indiana)', $result['America']['America/Indiana/Indianapolis']);

    // Make sure grouping hasn't erroneously created an entry with just the
    // first and second levels.
    $this->assertArrayNotHasKey('America/Indiana', $result['America']);

    // Make sure grouping hasn't duplicated an entry with just the first and
    // third levels.
    $this->assertArrayNotHasKey('America/Indianapolis', $result['America']);

    // Make sure that a grouped item isn't duplicated at the top level of the
    // results array.
    $this->assertArrayNotHasKey('America/Indiana/Indianapolis', $result);

  }

}
