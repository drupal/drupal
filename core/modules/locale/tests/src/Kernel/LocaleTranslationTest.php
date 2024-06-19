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
   * Tests that \Drupal\locale\LocaleTranslation is serializable.
   */
  public function testSerializable(): void {
    $translation = $this->container->get('string_translator.locale.lookup');
    $this->assertInstanceOf(LocaleTranslation::class, $translation);

    // Prove that serialization and deserialization works without errors.
    $this->assertNotNull($translation);
    $unserialized = unserialize(serialize($translation));
    $this->assertInstanceOf(LocaleTranslation::class, $unserialized);
  }

}
