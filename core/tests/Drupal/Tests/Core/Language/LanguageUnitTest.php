<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Language\LanguageUnitTest.
 */

namespace Drupal\Tests\Core\Language;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Language\Language
 * @group Language
 */
class LanguageUnitTest extends UnitTestCase {

  /**
   * The language under test.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->language = new Language();
  }

  /**
   * Tests name getter and setter methods.
   *
   * @covers ::getName()
   * @covers ::setName()
   */
  public function testGetName() {
    $name = $this->randomMachineName();
    $this->assertSame($this->language, $this->language->setName($name));
    $this->assertSame($name, $this->language->getName());
  }

  /**
   * Tests langcode ID getter and setter methods.
   *
   * @covers ::getId()
   * @covers ::setId()
   */
  public function testGetLangcode() {
    $language_code = $this->randomMachineName(2);
    $this->assertSame($this->language, $this->language->setId($language_code));
    $this->assertSame($language_code, $this->language->getId());
  }

  /**
   * Tests direction getter and setter methods.
   *
   * @covers ::getDirection()
   * @covers ::setDirection()
   */
  public function testGetDirection() {
    $direction = LanguageInterface::DIRECTION_RTL;
    $this->assertSame($this->language, $this->language->setDirection($direction));
    $this->assertSame($direction, $this->language->getDirection());
  }

  /**
   * Tests isDefault() and default setter.
   *
   * @covers ::isDefault()
   * @covers ::setDefault()
   */
  public function testIsDefault() {
    $default = TRUE;
    $this->assertSame($this->language, $this->language->setDefault($default));
    $this->assertSame($default, $this->language->isDefault());
  }

  /**
   * Tests negotiationMethodId getter and setter methods.
   *
   * @covers ::getNegotiationMethodId()
   * @covers ::setNegotiationMethodId()
   */
  public function testGetNegotiationMethodId() {
    $method_id = $this->randomMachineName();
    $this->assertSame($this->language, $this->language->setNegotiationMethodId($method_id));
    $this->assertSame($method_id, $this->language->getNegotiationMethodId());
  }

  /**
   * Tests sorting an array of Language objects.
   *
   * @covers ::sort()
   *
   * @dataProvider providerTestSortArrayOfLanguages
   *
   * @param \Drupal\Core\Language\Language[] $languages
   *   An array of \Drupal\Core\Language\Language objects.
   * @param array $expected
   *   The expected array of keys.
   */
  public function testSortArrayOfLanguages(array $languages, array $expected) {
    Language::sort($languages);
    $this->assertSame($expected, array_keys($languages));
  }

  /**
   * Provides data for testSortArrayOfLanguages.
   *
   * @return array
   *   An array of test data.
   */
  public function providerTestSortArrayOfLanguages() {
    $language9A = new Language();
    $language9A->setName('A');
    $language9A->setWeight(9);
    $language9A->setId('dd');

    $language10A = new Language();
    $language10A->setName('A');
    $language10A->setWeight(10);
    $language10A->setId('ee');

    $language10B = new Language();
    $language10B->setName('B');
    $language10B->setWeight(10);
    $language10B->setId('ff');

    return array(
      // Set up data set #0, already ordered by weight.
      array(
        // Set the data.
        array(
          $language9A->getId() => $language9A,
          $language10B->getId() => $language10B,
        ),
        // Set the expected key order.
        array(
          $language9A->getId(),
          $language10B->getId(),
        ),
      ),
      // Set up data set #1, out of order by weight.
      array(
        array(
          $language10B->getId() => $language10B,
          $language9A->getId() => $language9A,
        ),
        array(
          $language9A->getId(),
          $language10B->getId(),
        ),
      ),
      // Set up data set #2, tied by weight, already ordered by name.
      array(
        array(
          $language10A->getId() => $language10A,
          $language10B->getId() => $language10B,
        ),
        array(
          $language10A->getId(),
          $language10B->getId(),
        ),
      ),
      // Set up data set #3, tied by weight, out of order by name.
      array(
        array(
          $language10B->getId() => $language10B,
          $language10A->getId() => $language10A,
        ),
        array(
          $language10A->getId(),
          $language10B->getId(),
        ),
      ),
    );
  }

}
