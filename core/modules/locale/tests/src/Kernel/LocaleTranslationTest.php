<?php

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
  public function testSerializable() {
    $translation = $this->container->get('string_translator.locale.lookup');
    $this->assertInstanceOf(LocaleTranslation::class, $translation);

    // Prove that serialization and deserialization works without errors.
    $this->assertNotNull($translation);
    $unserialized = unserialize(serialize($translation));
    $this->assertInstanceOf(LocaleTranslation::class, $unserialized);

    // Ensure that all properties on the unserialized object match the original.
    $translation_reflection = new \ReflectionObject($translation);
    $unserialized_reflection = new \ReflectionObject($unserialized);
    // Ignore the '_serviceIds' property in the comparison.
    $properties = array_filter($translation_reflection->getProperties(), function ($value) {
      return $value->getName() !== '_serviceIds';
    });
    foreach ($properties as $value) {
      $value->setAccessible(TRUE);
      $unserialized_property = $unserialized_reflection->getProperty($value->getName());
      $unserialized_property->setAccessible(TRUE);
      $this->assertEquals($unserialized_property->getValue($unserialized), $value->getValue($translation));
    }
  }

}
