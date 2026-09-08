<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\StringTranslation\StringTranslationTrait.
 */
#[CoversClass(StringTranslationTrait::class)]
#[Group('StringTranslation')]
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

    $translation = $this->getStringTranslationStub();

    // Set up the object under test.
    $this->testObject = new class() {

      use StringTranslationTrait;

    };
    $this->testObject->setStringTranslation($translation);
  }

  /**
   * Tests t().
   *
   * @legacy-covers ::t
   */
  public function testT(): void {
    $invokableT = new \ReflectionMethod($this->testObject, 't');
    $result = $invokableT->invoke($this->testObject, 'something');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertEquals('something', $result);
  }

  /**
   * Tests formatPlural().
   *
   * @legacy-covers ::formatPlural
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
