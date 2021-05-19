<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration overrides via $config in settings.php.
 *
 * @group config
 */
class ConfigOverrideTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'config_test'];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system']);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));
  }

  /**
   * Tests configuration override.
   */
  public function testConfOverride() {
    $expected_original_data = [
      'foo' => 'bar',
      'baz' => NULL,
      '404' => 'herp',
    ];

    // Set globals before installing to prove that the installed file does not
    // contain these values.
    $overrides['config_test.system']['foo'] = 'overridden';
    $overrides['config_test.system']['baz'] = 'injected';
    $overrides['config_test.system']['404'] = 'derp';
    $GLOBALS['config'] = $overrides;

    $this->installConfig(['config_test']);

    // Verify that the original configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $active = $this->container->get('config.storage');
    $data = $active->read('config_test.system');
    $this->assertSame($expected_original_data['foo'], $data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertSame($expected_original_data['404'], $data['404']);

    // Get the configuration object with overrides.
    $config = \Drupal::configFactory()->get('config_test.system');

    // Verify that it contains the overridden data from $config.
    $this->assertSame($overrides['config_test.system']['foo'], $config->get('foo'));
    $this->assertSame($overrides['config_test.system']['baz'], $config->get('baz'));
    $this->assertSame($overrides['config_test.system']['404'], $config->get('404'));

    // Get the configuration object which does not have overrides.
    $config = \Drupal::configFactory()->getEditable('config_test.system');

    // Verify that it does not contains the overridden data from $config.
    $this->assertSame($expected_original_data['foo'], $config->get('foo'));
    $this->assertNull($config->get('baz'));
    $this->assertSame($expected_original_data['404'], $config->get('404'));

    // Set the value for 'baz' (on the original data).
    $expected_original_data['baz'] = 'original baz';
    $config->set('baz', $expected_original_data['baz']);

    // Set the value for '404' (on the original data).
    $expected_original_data['404'] = 'original 404';
    $config->set('404', $expected_original_data['404']);

    // Save the configuration object (having overrides applied).
    $config->save();

    // Reload it and verify that it still contains overridden data from $config.
    $config = \Drupal::config('config_test.system');
    $this->assertSame($overrides['config_test.system']['foo'], $config->get('foo'));
    $this->assertSame($overrides['config_test.system']['baz'], $config->get('baz'));
    $this->assertSame($overrides['config_test.system']['404'], $config->get('404'));

    // Verify that raw config data has changed.
    $this->assertSame($expected_original_data['foo'], $config->getOriginal('foo', FALSE));
    $this->assertSame($expected_original_data['baz'], $config->getOriginal('baz', FALSE));
    $this->assertSame($expected_original_data['404'], $config->getOriginal('404', FALSE));

    // Write file to sync.
    $sync = $this->container->get('config.storage.sync');
    $expected_new_data = [
      'foo' => 'barbar',
      '404' => 'herpderp',
    ];
    $sync->write('config_test.system', $expected_new_data);

    // Import changed data from sync to active.
    $this->configImporter()->import();
    $data = $active->read('config_test.system');

    // Verify that the new configuration data exists. Have to read storage
    // directly otherwise overrides will apply.
    $this->assertSame($expected_new_data['foo'], $data['foo']);
    $this->assertFalse(isset($data['baz']));
    $this->assertSame($expected_new_data['404'], $data['404']);

    // Verify that the overrides are still working.
    $config = \Drupal::config('config_test.system');
    $this->assertSame($overrides['config_test.system']['foo'], $config->get('foo'));
    $this->assertSame($overrides['config_test.system']['baz'], $config->get('baz'));
    $this->assertSame($overrides['config_test.system']['404'], $config->get('404'));

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $GLOBALS['config']['config_test.new']['key'] = 'override';
    $config = \Drupal::config('config_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_test.new is new');
    $this->assertSame('override', $config->get('key'));
    $config_raw = \Drupal::configFactory()->getEditable('config_test.new');
    $this->assertNull($config_raw->get('key'));
    $config_raw
      ->set('key', 'raw')
      ->set('new_key', 'new_value')
      ->save();
    // Ensure override is preserved but all other data has been updated
    // accordingly.
    $config = \Drupal::config('config_test.new');
    $this->assertFalse($config->isNew(), 'The configuration object config_test.new is not new');
    $this->assertSame('override', $config->get('key'));
    $this->assertSame('new_value', $config->get('new_key'));
    $raw_data = $config->getRawData();
    $this->assertSame('raw', $raw_data['key']);
  }

}
