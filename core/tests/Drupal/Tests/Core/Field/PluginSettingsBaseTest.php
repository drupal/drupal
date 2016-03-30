<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Field\PluginSettingsBaseTest.
 */

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Field\PluginSettingsBase
 * @group Field
 */
class PluginSettingsBaseTest extends UnitTestCase {

  /**
   * @covers ::getThirdPartySettings
   */
  public function testGetThirdPartySettings() {
    $plugin_settings = new TestPluginSettingsBase();
    $this->assertSame([], $plugin_settings->getThirdPartySettings());
    $this->assertSame([], $plugin_settings->getThirdPartySettings('test'));
    $plugin_settings->setThirdPartySetting('test', 'foo', 'bar');
    $this->assertSame(['foo' => 'bar'], $plugin_settings->getThirdPartySettings('test'));
    $this->assertSame([], $plugin_settings->getThirdPartySettings('test2'));
  }

}

class TestPluginSettingsBase extends PluginSettingsBase {

  public function __construct() {
  }

}
