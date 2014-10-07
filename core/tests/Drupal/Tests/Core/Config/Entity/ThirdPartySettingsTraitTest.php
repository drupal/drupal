<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ThirdPartySettingsTraitTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\Config\Entity\ThirdPartySettingsTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ThirdPartySettingsTrait
 * @group Config
 */
class ThirdPartySettingsTraitTest extends UnitTestCase {

  /**
   * @covers ::getThirdPartySetting
   * @covers ::setThirdPartySetting
   * @covers ::getThirdPartySettings
   * @covers ::unsetThirdPartySetting
   * @covers ::getThirdPartyProviders
   */
  public function testThirdPartySettings() {
    $key = 'test';
    $third_party = 'test_provider';
    $value = $this->getRandomGenerator()->string();

    $trait_object = new TestThirdPartySettingsTrait();

    // Test getThirdPartySetting() with no settings.
    $this->assertEquals($value, $trait_object->getThirdPartySetting($third_party, $key, $value));
    $this->assertNull($trait_object->getThirdPartySetting($third_party, $key));

    // Test setThirdPartySetting().
    $trait_object->setThirdPartySetting($third_party, $key, $value);
    $this->assertEquals($value, $trait_object->getThirdPartySetting($third_party, $key));
    $this->assertEquals($value, $trait_object->getThirdPartySetting($third_party, $key, $this->randomGenerator->string()));

    // Test getThirdPartySettings().
    $trait_object->setThirdPartySetting($third_party, 'test2', 'value2');
    $this->assertEquals(array($key => $value, 'test2' => 'value2'), $trait_object->getThirdPartySettings($third_party));

    // Test getThirdPartyProviders().
    $trait_object->setThirdPartySetting('test_provider2', $key, $value);
    $this->assertEquals(array($third_party, 'test_provider2'), $trait_object->getThirdPartyProviders());

    // Test unsetThirdPartyProviders().
    $trait_object->unsetThirdPartySetting('test_provider2', $key);
    $this->assertEquals(array($third_party), $trait_object->getThirdPartyProviders());
  }
}

class TestThirdPartySettingsTrait {

  use ThirdPartySettingsTrait;

}
