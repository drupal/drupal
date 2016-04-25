<?php

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
   * @covers ::__construct
   */
  public function testConstruct() {
    $name = $this->randomMachineName();
    $language_code = $this->randomMachineName(2);
    $uuid = $this->randomMachineName();
    $language = new Language(array('id' => $language_code, 'name' => $name, 'uuid' => $uuid));
    // Test that nonexistent properties are not added to the language object.
    $this->assertTrue(property_exists($language, 'id'));
    $this->assertTrue(property_exists($language, 'name'));
    $this->assertFalse(property_exists($language, 'uuid'));
  }

  /**
   * @covers ::getName
   */
  public function testGetName() {
    $name = $this->randomMachineName();
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code, 'name' => $name));
    $this->assertSame($name, $language->getName());
  }

  /**
   * @covers ::getId
   */
  public function testGetLangcode() {
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code));
    $this->assertSame($language_code, $language->getId());
  }

  /**
   * @covers ::getDirection
   */
  public function testGetDirection() {
    $language_code = $this->randomMachineName(2);
    $language = new Language(array('id' => $language_code, 'direction' => LanguageInterface::DIRECTION_RTL));
    $this->assertSame(LanguageInterface::DIRECTION_RTL, $language->getDirection());
  }

  /**
   * @covers ::isDefault
   */
  public function testIsDefault() {
    $language_default = $this->getMockBuilder('Drupal\Core\Language\LanguageDefault')->disableOriginalConstructor()->getMock();
    $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->any())
      ->method('get')
      ->with('language.default')
      ->will($this->returnValue($language_default));
    \Drupal::setContainer($container);

    $language = new Language(array('id' => $this->randomMachineName(2)));
    // Set up the LanguageDefault to return different default languages on
    // consecutive calls.
    $language_default->expects($this->any())
      ->method('get')
      ->willReturnOnConsecutiveCalls(
        $language,
        new Language(array('id' => $this->randomMachineName(2)))
      );

    $this->assertTrue($language->isDefault());
    $this->assertFalse($language->isDefault());
  }

  /**
   * Tests sorting an array of language objects.
   *
   * @covers ::sort
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
