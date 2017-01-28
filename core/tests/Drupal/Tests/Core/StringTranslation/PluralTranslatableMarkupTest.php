<?php

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TranslatableMarkup class.
 *
 * @coversDefaultClass \Drupal\Core\StringTranslation\PluralTranslatableMarkup
 * @group StringTranslation
 */
class PluralTranslatableMarkupTest extends UnitTestCase {

  /**
   * Tests serialization of PluralTranslatableMarkup().
   *
   * @dataProvider providerPluralTranslatableMarkupSerialization
   */
  public function testPluralTranslatableMarkupSerialization($count, $expected_text) {
    // Add a mock string translation service to the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    // Create an object to serialize and unserialize.
    $markup = new PluralTranslatableMarkup($count, 'singular @count', 'plural @count');
    $serialized_markup = unserialize(serialize($markup));
    $this->assertEquals($expected_text, $serialized_markup->render());
  }

  /**
   * Data provider for ::testPluralTranslatableMarkupSerialization().
   */
  public function providerPluralTranslatableMarkupSerialization() {
    return [
      [1, 'singular 1'],
      [2, 'plural 2'],
    ];
  }

}
