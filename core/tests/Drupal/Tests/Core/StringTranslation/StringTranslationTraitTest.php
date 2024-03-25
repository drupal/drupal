<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\StringTranslation\StringTranslationTrait
 * @group StringTranslation
 */
class StringTranslationTraitTest extends UnitTestCase {

  /**
   * The object under test that uses StringTranslationTrait.
   */
  protected object $testObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Prepare a mock translation service to pass to the trait.
    $translation = $this->prophesize(TranslationInterface::class);
    $translation->translate(Argument::cetera())->shouldNotBeCalled();
    $translation->formatPlural(Argument::cetera())->shouldNotBeCalled();
    $translation->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });

    // Set up the object under test.
    $this->testObject = new class() {

      use StringTranslationTrait;

    };
    $this->testObject->setStringTranslation($translation->reveal());
  }

  /**
   * @covers ::t
   */
  public function testT(): void {
    $invokableT = new \ReflectionMethod($this->testObject, 't');
    $result = $invokableT->invoke($this->testObject, 'something');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertEquals('something', $result);
  }

  /**
   * @covers ::formatPlural
   */
  public function testFormatPlural(): void {
    $invokableFormatPlural = new \ReflectionMethod($this->testObject, 'formatPlural');
    $result = $invokableFormatPlural->invoke($this->testObject, 1, 'apple', 'apples');
    $this->assertInstanceOf(PluralTranslatableMarkup::class, $result);
    $this->assertEquals('apple', $result);
    $result = $invokableFormatPlural->invoke($this->testObject, 2, 'apple', 'apples');
    $this->assertInstanceOf(PluralTranslatableMarkup::class, $result);
    $this->assertEquals('apples', $result);
  }

}
