<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
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
   * A reflection of self::$translation.
   *
   * @var \ReflectionClass
   */
  protected $reflection;

  /**
   * The mock under test that uses StringTranslationTrait.
   *
   * @var object
   * @see \PHPUnit\Framework\MockObject\Generator::getObjectForTrait()
   */
  protected $translation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->translation = $this->getObjectForTrait('\Drupal\Core\StringTranslation\StringTranslationTrait');
    $mock = $this->prophesize(TranslationInterface::class);
    $mock->translate(Argument::cetera())->shouldNotBeCalled();
    $mock->formatPlural(Argument::cetera())->shouldNotBeCalled();
    $mock->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });
    $this->translation->setStringTranslation($mock->reveal());
    $this->reflection = new \ReflectionClass(get_class($this->translation));
  }

  /**
   * @covers ::t
   */
  public function testT() {
    $method = $this->reflection->getMethod('t');

    $result = $method->invoke($this->translation, 'something');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertEquals('something', $result);
  }

  /**
   * @covers ::formatPlural
   */
  public function testFormatPlural() {
    $method = $this->reflection->getMethod('formatPlural');

    $result = $method->invoke($this->translation, 2, 'apple', 'apples');
    $this->assertInstanceOf(PluralTranslatableMarkup::class, $result);
    $this->assertEquals('apples', $result);
  }

}
