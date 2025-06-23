<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\locale\LocaleTranslation;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\locale\LocaleTranslation
 * @group locale
 */
class LocaleTranslationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);
  }

  /**
   * Tests that \Drupal\locale\LocaleTranslation is serializable.
   */
  public function testSerializable(): void {
    /** @var \Drupal\locale\LocaleTranslation $translation */
    $translation = $this->container->get('string_translator.locale.lookup');
    $this->assertInstanceOf(LocaleTranslation::class, $translation);
    // Ensure that the \Drupal\locale\LocaleTranslation::$translations property
    // has some cached translations in it. Without this, serialization will not
    // actually be tested fully.
    $translation->getStringTranslation('es', 'test', '');

    // Prove that serialization and deserialization works without errors.
    $this->assertNotNull($translation);
    $unserialized = unserialize(serialize($translation));
    $this->assertInstanceOf(LocaleTranslation::class, $unserialized);
  }

}
