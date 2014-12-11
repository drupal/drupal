<?php

/**
 * @file
 * Definition of Drupal\config\Tests\ConfigOverrideTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests configuration overrides via $config in settings.php.
 *
 * @group config
 */
class ConfigOverrideTest extends KernelTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'config_test');

  protected function setUp() {
    parent::setUp();
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.staging'));
  }

  /**
   * Tests configuration override.
   */
  function testConfOverride() {
    $expected_original_data = array(
      'foo' => 'bar',
      'baz' => NULL,
      '404' => 'herp',
    );

    // Set globals before installing to prove that the installed file does not
    // contain these values.
    $overrides['config_test.system']['foo'] = 'overridden';
    $overrides['config_test.system']['baz'] = 'injected';
    $overrides['config_test.system']['404'] = 'derp';
    $GLOBALS['config'] = $overrides;

    $this->installConfig(array('config_test'));

    // Verify that the original configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $active = $this->container->get('config.storage');
    $data = $active->read('config_test.system');
    $this->assertIdentical($data['foo'], $expected_original_data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertIdentical($data['404'], $expected_original_data['404']);

    // Get the configuration object in with overrides.
    $config = \Drupal::config('config_test.system');

    // Verify that it contains the overridden data from $config.
    $this->assertIdentical($config->get('foo'), $overrides['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $overrides['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $overrides['config_test.system']['404']);

    // Set the value for 'baz' (on the original data).
    $expected_original_data['baz'] = 'original baz';
    $config->set('baz', $expected_original_data['baz']);

    // Set the value for '404' (on the original data).
    $expected_original_data['404'] = 'original 404';
    $config->set('404', $expected_original_data['404']);

    // Verify that it still contains the overridden data from $config.
    $this->assertIdentical($config->get('foo'), $overrides['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $overrides['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $overrides['config_test.system']['404']);

    // Save the configuration object (having overrides applied).
    $config->save();

    // Reload it and verify that it still contains overridden data from $config.
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), $overrides['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $overrides['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $overrides['config_test.system']['404']);

    // Write file to staging.
    $staging = $this->container->get('config.storage.staging');
    $expected_new_data = array(
      'foo' => 'barbar',
      '404' => 'herpderp',
    );
    $staging->write('config_test.system', $expected_new_data);

    // Import changed data from staging to active.
    $this->configImporter()->import();
    $data = $active->read('config_test.system');

    // Verify that the new configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $this->assertIdentical($data['foo'], $expected_new_data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertIdentical($data['404'], $expected_new_data['404']);

    // Verifiy the overrides are still working.
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), $overrides['config_test.system']['foo']);
    $this->assertIdentical($config->get('baz'), $overrides['config_test.system']['baz']);
    $this->assertIdentical($config->get('404'), $overrides['config_test.system']['404']);

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $GLOBALS['config']['config_test.new']['key'] = 'override';
    $config = \Drupal::config('config_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_test.new is new');
    $this->assertIdentical($config->get('key'), 'override');
    $old_state = \Drupal::configFactory()->getOverrideState();
    \Drupal::configFactory()->setOverrideState(FALSE);
    $config_raw = \Drupal::config('config_test.new');
    $this->assertIdentical($config_raw->get('key'), NULL);
    $config_raw
      ->set('key', 'raw')
      ->set('new_key', 'new_value')
      ->save();
    \Drupal::configFactory()->setOverrideState($old_state);
    // Ensure override is preserved but all other data has been updated
    // accordingly.
    $this->assertIdentical($config->get('key'), 'override');
    $this->assertIdentical($config->get('new_key'), 'new_value');
    $raw_data = $config->getRawData();
    $this->assertIdentical($raw_data['key'], 'raw');
  }

}
