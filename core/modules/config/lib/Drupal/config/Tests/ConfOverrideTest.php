<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests configuration overriding from settings.php.
 */
class ConfOverrideTest extends WebTestBase {
  protected $testContent = 'Good morning, Denver!';

  public static function getInfo() {
    return array(
      'name' => 'Configuration overrides',
      'description' => 'Tests configuration overrides through settings.php.',
      'group' => 'Configuration',
    );
  }

  /**
   * Test configuration override.
   */
  function testConfigurationOverride() {
    global $conf;
    $config = config('system.performance');
    $this->assertNotEqual($config->get('cache'), $this->testContent);

    $conf['system.performance']['cache'] = $this->testContent;
    $config = config('system.performance');
    $this->assertEqual($config->get('cache'), $conf['system.performance']['cache']);
  }
}
