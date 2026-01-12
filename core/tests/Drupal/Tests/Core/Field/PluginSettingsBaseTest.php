<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Field\PluginSettingsBase.
 */
#[CoversClass(PluginSettingsBase::class)]
#[Group('Field')]
class PluginSettingsBaseTest extends UnitTestCase {

  /**
   * Tests get third party settings.
   */
  public function testGetThirdPartySettings(): void {
    $plugin_settings = new TestPluginSettingsBase();
    $this->assertSame([], $plugin_settings->getThirdPartySettings());
    $this->assertSame([], $plugin_settings->getThirdPartySettings('test'));
    $plugin_settings->setThirdPartySetting('test', 'foo', 'bar');
    $this->assertSame(['foo' => 'bar'], $plugin_settings->getThirdPartySettings('test'));
    $this->assertSame([], $plugin_settings->getThirdPartySettings('test2'));
  }

}

/**
 * Stub class for testing PluginSettingsBase.
 */
class TestPluginSettingsBase extends PluginSettingsBase {

  public function __construct() {
  }

}
