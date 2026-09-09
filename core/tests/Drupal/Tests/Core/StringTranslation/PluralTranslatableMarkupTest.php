<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TranslatableMarkup class.
 */
#[CoversClass(PluralTranslatableMarkup::class)]
#[Group('StringTranslation')]
class PluralTranslatableMarkupTest extends UnitTestCase {

  /**
   * Tests serialization of PluralTranslatableMarkup().
   */
  #[DataProvider('providerPluralTranslatableMarkupSerialization')]
  public function testPluralTranslatableMarkupSerialization($count, $expected_text): void {
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
  public static function providerPluralTranslatableMarkupSerialization(): array {
    return [
      [1, 'singular 1'],
      [2, 'plural 2'],
    ];
  }

  /**
   * Tests when the plural translation is missing.
   */
  public function testMissingPluralTranslation(): void {
    // Add a mock string translation service to the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $markup = PluralTranslatableMarkup::createFromTranslatedString(2, 'There is no plural delimiter @count');
    $this->assertEquals('There is no plural delimiter 2', $markup->render());
  }

  /**
   * Tests that an explicit @count in the arguments takes precedence.
   */
  #[DataProvider('providerExplicitCountArgument')]
  public function testExplicitCountArgument(int|float $count, string $explicit_count, string $expected_text): void {
    // Add a mock string translation service to the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $markup = new PluralTranslatableMarkup($count, 'singular @count', 'plural @count', ['@count' => $explicit_count]);
    $this->assertEquals($expected_text, $markup->render());
  }

  /**
   * Data provider for ::testExplicitCountArgument().
   */
  public static function providerExplicitCountArgument(): array {
    return [
      // The plural form is selected with the count, but the explicitly passed
      // replacement is what gets displayed.
      'singular' => [1, 'one', 'singular one'],
      'plural' => [2, 'a couple of', 'plural a couple of'],
      // A fractional count selects the plural form, while the argument keeps
      // control over the formatting of the displayed value.
      'fraction' => [1.5, '1½', 'plural 1½'],
    ];
  }

}
