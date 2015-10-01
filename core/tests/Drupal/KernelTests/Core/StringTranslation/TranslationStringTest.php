<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\StringTranslation\TranslationStringTest.
 */

namespace Drupal\KernelTests\Core\StringTranslation;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the TranslatableMarkup class.
 *
 * @group StringTranslation
 */
class TranslationStringTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Tests that TranslatableMarkup objects can be compared.
   */
  public function testComparison() {
    $this->rebootAndPrepareSettings();
    $a = \Drupal::service('string_translation')->translate('Example @number', ['@number' => 42], ['langcode' => 'de']);

    $this->rebootAndPrepareSettings();
    $b = \Drupal::service('string_translation')->translate('Example @number', ['@number' => 42], ['langcode' => 'de']);
    $c = \Drupal::service('string_translation')->translate('Example @number', ['@number' => 43], ['langcode' => 'de']);
    $d = \Drupal::service('string_translation')->translate('Example @number', ['@number' => 42], ['langcode' => 'en']);

    // The two objects have the same settings so == comparison will work.
    $this->assertEquals($a, $b);
    // The two objects are not the same object.
    $this->assertNotSame($a, $b);
    // TranslationWrappers which have different settings are not equal.
    $this->assertNotEquals($a, $c);
    $this->assertNotEquals($a, $d);
  }

  /**
   * Reboots the kernel to set custom translations in Settings.
   */
  protected function rebootAndPrepareSettings() {
    // Reboot the container so that different services are injected and the new
    // settings are picked.
    $kernel = $this->container->get('kernel');
    $kernel->shutdown();
    $kernel->boot();
    $settings = Settings::getAll();
    $settings['locale_custom_strings_de'] = ['' => ['Example @number' => 'Example @number translated']];
    // Recreate the settings static.
    new Settings($settings);
  }

}
