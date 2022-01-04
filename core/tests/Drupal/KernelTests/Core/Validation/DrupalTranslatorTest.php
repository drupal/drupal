<?php

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Validation\DrupalTranslator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the for translator.
 *
 * @coversDefaultClass \Drupal\Core\Validation\DrupalTranslator
 *
 * @group validation
 */
class DrupalTranslatorTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Set up a mock container as transChoice() will call for the
    // 'string_translation' service.
    $plural_translatable_markup = $this->getMockBuilder(PluralTranslatableMarkup::class)
      ->disableOriginalConstructor()
      ->getMock();

    $translation_manager = $this->getMockBuilder(TranslationManager::class)
      ->onlyMethods(['formatPlural'])
      ->disableOriginalConstructor()
      ->getMock();
    $translation_manager->expects($this->any())
      ->method('formatPlural')
      ->willReturn($plural_translatable_markup);

    $container = new ContainerBuilder();
    $container->set('string_translation', $translation_manager);

    \Drupal::setContainer($container);
  }

  /**
   * Test transChoice deprecation message.
   *
   * @covers ::transChoice
   * @group legacy
   */
  public function testDeprecation() {
    $this->expectDeprecation('Drupal\Core\Validation\DrupalTranslator::transChoice() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use DrupalTranslator::trans() instead. See https://www.drupal.org/node/3255250');
    $translator = new DrupalTranslator();
    $this->assertInstanceOf(
      PluralTranslatableMarkup::class,
      $translator->transChoice('There is one apple | There are @count apples', 1)
    );
  }

}
