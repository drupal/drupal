<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\RegionContentTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests drupal_add_region_content() and drupal_get_region_content().
 */
class RegionContentTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Region content',
      'description' => 'Tests setting and retrieving content from theme regions.',
      'group' => 'Common',
    );
  }

  /**
   * Tests setting and retrieving content for theme regions.
   */
  function testRegions() {
    global $theme_key;

    $block_regions = array_keys(system_region_list($theme_key));
    $delimiter = $this->randomName(32);
    $values = array();
    // Set some random content for each region available.
    foreach ($block_regions as $region) {
      $first_chunk = $this->randomName(32);
      drupal_add_region_content($region, $first_chunk);
      $second_chunk = $this->randomName(32);
      drupal_add_region_content($region, $second_chunk);
      // Store the expected result for a drupal_get_region_content call for this region.
      $values[$region] = $first_chunk . $delimiter . $second_chunk;
    }

    // Ensure drupal_get_region_content returns expected results when fetching all regions.
    $content = drupal_get_region_content(NULL, $delimiter);
    foreach ($content as $region => $region_content) {
      $this->assertEqual($region_content, $values[$region], format_string('@region region text verified when fetching all regions', array('@region' => $region)));
    }

    // Ensure drupal_get_region_content returns expected results when fetching a single region.
    foreach ($block_regions as $region) {
      $region_content = drupal_get_region_content($region, $delimiter);
      $this->assertEqual($region_content, $values[$region], format_string('@region region text verified when fetching single region.', array('@region' => $region)));
    }
  }
}
