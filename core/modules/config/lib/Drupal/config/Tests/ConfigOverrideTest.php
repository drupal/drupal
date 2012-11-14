<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests configuration overrides via $conf in settings.php.
 */
class ConfigOverrideTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration overrides',
      'description' => 'Tests configuration overrides via $conf in settings.php.',
      'group' => 'Configuration',
    );
  }

  function setUp() {
    parent::setUp();

    config_install_default_config('module', 'config_test');
  }

  /**
   * Tests configuration override.
   */
  function testConfOverride() {
    global $conf;
    $expected_original_data = array(
      'foo' => 'bar',
      'baz' => NULL,
      '404' => 'herp',
    );

    // Verify that the original configuration data exists.
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);

    // Apply the overridden data.
    $conf['config_test.system']['foo'] = 'overridden';
    $conf['config_test.system']['baz'] = 'injected';
    $conf['config_test.system']['404'] = 'derp';

    // Verify that the in-memory configuration object still contains the
    // original data.
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);

    // Reload the configuration object.
    $config = config('config_test.system');

    // Verify that it contains the overridden data from $conf.
    $this->assertIdentical($config->get('foo'), $conf['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $conf['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $conf['config_test.system']['404']);

    // Set the value for 'baz' (on the original data).
    $expected_original_data['baz'] = 'original baz';
    $config->set('baz', $expected_original_data['baz']);

    // Set the value for '404' (on the original data).
    $expected_original_data['404'] = 'original 404';
    $config->set('404', $expected_original_data['404']);

    // Verify that it still contains the overridden data from $conf.
    $this->assertIdentical($config->get('foo'), $conf['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $conf['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $conf['config_test.system']['404']);

    // Save the configuration object (having overrides applied).
    $config->save();

    // Reload it and verify that it still contains overridden data from $conf.
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), $conf['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $conf['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $conf['config_test.system']['404']);

    // Remove the $conf overrides.
    unset($conf['config_test.system']);

    // Reload it and verify that it still contains the original data.
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);
  }

}
