<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvents;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests events fired on configuration objects.
 *
 * @group config
 */
class ConfigEventsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['config_events_test'];

  /**
   * Tests configuration events.
   */
  public function testConfigEvents() {
    $name = 'config_events_test.test';

    $config = new Config($name, \Drupal::service('config.storage'), \Drupal::service('event_dispatcher'), \Drupal::service('config.typed'));
    $config->set('key', 'initial');
    $this->assertSame([], \Drupal::state()->get('config_events_test.event', []), 'No events fired by creating a new configuration object');
    $config->save();

    $event = \Drupal::state()->get('config_events_test.event', []);
    $this->assertSame(ConfigEvents::SAVE, $event['event_name']);
    $this->assertSame(['key' => 'initial'], $event['current_config_data']);
    $this->assertSame(['key' => 'initial'], $event['raw_config_data']);
    $this->assertSame([], $event['original_config_data']);

    $config->set('key', 'updated')->save();
    $event = \Drupal::state()->get('config_events_test.event', []);
    $this->assertSame(ConfigEvents::SAVE, $event['event_name']);
    $this->assertSame(['key' => 'updated'], $event['current_config_data']);
    $this->assertSame(['key' => 'updated'], $event['raw_config_data']);
    $this->assertSame(['key' => 'initial'], $event['original_config_data']);

    $config->delete();
    $event = \Drupal::state()->get('config_events_test.event', []);
    $this->assertSame(ConfigEvents::DELETE, $event['event_name']);
    $this->assertSame([], $event['current_config_data']);
    $this->assertSame([], $event['raw_config_data']);
    $this->assertSame(['key' => 'updated'], $event['original_config_data']);
  }

  /**
   * Tests configuration rename event that is fired from the ConfigFactory.
   */
  public function testConfigRenameEvent() {
    $name = 'config_events_test.test';
    $new_name = 'config_events_test.test_rename';
    $GLOBALS['config'][$name] = ['key' => 'overridden'];
    $GLOBALS['config'][$new_name] = ['key' => 'new overridden'];

    $config = $this->config($name);
    $config->set('key', 'initial')->save();
    $event = \Drupal::state()->get('config_events_test.event', []);
    $this->assertSame(ConfigEvents::SAVE, $event['event_name']);
    $this->assertSame(['key' => 'initial'], $event['current_config_data']);

    // Override applies when getting runtime config.
    $this->assertEqual($GLOBALS['config'][$name], \Drupal::config($name)->get());

    \Drupal::configFactory()->rename($name, $new_name);
    $event = \Drupal::state()->get('config_events_test.event', []);
    $this->assertSame(ConfigEvents::RENAME, $event['event_name']);
    $this->assertSame(['key' => 'new overridden'], $event['current_config_data']);
    $this->assertSame(['key' => 'initial'], $event['raw_config_data']);
    $this->assertSame(['key' => 'new overridden'], $event['original_config_data']);
  }

}
