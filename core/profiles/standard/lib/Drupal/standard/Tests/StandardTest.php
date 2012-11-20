<?php

/**
 * @file
 * Contains Drupal\standard\Tests\StandardTest.
 */

namespace Drupal\standard\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Standard installation profile expectations.
 */
class StandardTest extends WebTestBase {

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Standard installation profile',
      'description' => 'Tests Standard installation profile expectations.',
      'group' => 'Standard',
    );
  }

  /**
   * Tests Standard installation profile.
   */
  function testStandard() {
    $this->drupalGet('');
    $this->assertLink(t('Contact'));
    $this->clickLink(t('Contact'));
    $this->assertResponse(200);
  }

}
