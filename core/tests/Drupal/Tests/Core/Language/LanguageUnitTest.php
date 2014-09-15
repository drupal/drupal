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
   * @covers ::getName()
   */
  public function testGetName() {
    $name = $this->randomMachineName();
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code, 'name' => $name));
    $this->assertSame($name, $language->getName());
  }

  /**
   * @covers ::getId()
   */
  public function testGetLangcode() {
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code));
    $this->assertSame($language_code, $language->getId());
  }

  /**
   * @covers ::getDirection()
   */
  public function testGetDirection() {
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code, 'direction' => LanguageInterface::DIRECTION_RTL));
    $this->assertSame(LanguageInterface::DIRECTION_RTL, $language->getDirection());
  }

  /**
   * @covers ::isDefault()
   */
  public function testIsDefault() {
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code, 'default' => TRUE));
    $this->assertTrue($language->isDefault());
  }

  /**
   * Tests sorting an array of language objects.
   *
   * @covers ::sort()
   *
   * @dataProvider providerTestSortArrayOfLanguages
   *
   * @param \Drupal\Core\Language\LanguageInterface[] $languages
   *   An array of language objects.
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
    $language9A = new Language(array('id' => 'dd', 'name' => 'A', 'weight' => 9));
    $language10A = new Language(array('id' => 'ee', 'name' => 'A', 'weight' => 10));
    $language10B = new Language(array('id' => 'ff', 'name' => 'B', 'weight' => 10));

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
