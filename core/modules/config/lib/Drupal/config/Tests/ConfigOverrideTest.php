<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Core\Config\Context\ConfigContext;

/**
 * Tests configuration overrides via $conf in settings.php.
 */
class ConfigOverrideTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'config_test');

  public static function getInfo() {
    return array(
      'name' => 'Configuration overrides',
      'description' => 'Tests configuration overrides via $conf in settings.php.',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installSchema('system', 'config_snapshot');
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

    // Set globals before installing to prove that the installed file does not
    // contain these values.
    $conf['config_test.system']['foo'] = 'overridden';
    $conf['config_test.system']['baz'] = 'injected';
    $conf['config_test.system']['404'] = 'derp';
    drupal_container()->get('config.context')->setGlobalOverride();

    config_install_default_config('module', 'config_test');

    // Verify that the original configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $active = $this->container->get('config.storage');
    $data = $active->read('config_test.system');
    $this->assertIdentical($data['foo'], $expected_original_data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertIdentical($data['404'], $expected_original_data['404']);

    // Remove the $conf overrides and reset value in config.context service.
    unset($conf['config_test.system']);
    drupal_container()->get('config.context')->setGlobalOverride();

    // Verify that the original configuration data exists.
    $config = config('config_test.system');
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);

    // Apply the overridden data, that needs to be set into the config.context
    // service.
    $conf['config_test.system']['foo'] = 'overridden';
    $conf['config_test.system']['baz'] = 'injected';
    $conf['config_test.system']['404'] = 'derp';
    drupal_container()->get('config.context')->setGlobalOverride();

    // Verify that the in-memory configuration object still contains the
    // original data.
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);

    // Reload the configuration object.
    $config->init();

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

    // Remove the $conf overrides and reset value in config.context service.
    unset($conf['config_test.system']);
    drupal_container()->get('config.context')->setGlobalOverride();

    // Reload it and verify that it still contains the original data.
    $config->init();
    $this->assertIdentical($config->get('foo'), $expected_original_data['foo']);
    $this->assertIdentical($config->get('baz'), $expected_original_data['baz']);
    $this->assertIdentical($config->get('404'), $expected_original_data['404']);

    // Set globals before importing to prove that the imported file does not
    // contain these values.
    $conf['config_test.system']['foo'] = 'overridden';
    $conf['config_test.system']['baz'] = 'injected';
    $conf['config_test.system']['404'] = 'derp';

    // Write file to staging
    drupal_container()->get('config.context')->setGlobalOverride();
    $staging = $this->container->get('config.storage.staging');
    $expected_new_data = array(
      'foo' => 'barbar',
      '404' => 'herpderp',
    );
    $staging->write('config_test.system', $expected_new_data);

    // Import changed data from staging to active.
    config_import();
    $data = $active->read('config_test.system');

    // Verify that the new configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $this->assertIdentical($data['foo'], $expected_new_data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertIdentical($data['404'], $expected_new_data['404']);

    // Verifiy the overrides are still working.
    $this->assertIdentical($config->get('foo'), $conf['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $conf['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $conf['config_test.system']['404']);
  }

}
