<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Tests that TranslatableMarkup objects can be compared.
   */
  public function testComparison(): void {
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
    // TranslatableMarkup which have different settings are not equal.
    $this->assertNotEquals($a, $c);
    $this->assertNotEquals($a, $d);
  }

  /**
   * Reboots the kernel to set custom translations in Settings.
   */
  protected function rebootAndPrepareSettings(): void {
    // Reboot the container so that different services are injected and the new
    // settings are picked.
    $kernel = $this->container->get('kernel');
    // @todo This used to call shutdown() and boot(). rebuildContainer() is
    // needed until we stop pushing the request twice and only popping it once.
    // @see https://www.drupal.org/i/2613044
    $kernel->rebuildContainer();
    $settings = Settings::getAll();
    $settings['locale_custom_strings_de'] = ['' => ['Example @number' => 'Example @number translated']];
    // Recreate the settings static.
    new Settings($settings);
  }

}
