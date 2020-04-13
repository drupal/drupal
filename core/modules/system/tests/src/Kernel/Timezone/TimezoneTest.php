<?php

namespace Drupal\Tests\system\Kernel\Timezone;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test coverage for time zone handling.
 *
 * @group system
 */
class TimezoneTest extends KernelTestBase {

  protected static $modules = ['system'];

  /**
   * Tests system_time_zones().
   */
  public function testSystemTimeZones() {
    // Test the default parameters for system_time_zones().
    $result = system_time_zones();
    $this->assertIsArray($result);
    $this->assertArrayHasKey('Africa/Dar_es_Salaam', $result);
    $this->assertEquals('Africa/Dar es Salaam', $result['Africa/Dar_es_Salaam']);

    // Tests time zone grouping.
    $result = system_time_zones(NULL, TRUE);

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

    // Test that the ungrouped and grouped results have the same number of
    // items.
    $ungrouped_count = count(system_time_zones());
    $grouped_result = system_time_zones(NULL, TRUE);
    $grouped_count = 0;
    array_walk_recursive($grouped_result, function () use (&$grouped_count) {
      $grouped_count++;
    });
    $this->assertEquals($ungrouped_count, $grouped_count);
  }

}
