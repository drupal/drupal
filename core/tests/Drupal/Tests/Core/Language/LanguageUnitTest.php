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
 *
 * @group Drupal
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
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Language\Language unit test',
      'group' => 'System',
    );
  }

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
    $name = $this->randomName();
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
    $language_code = $this->randomName(2);
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
    $method_id = $this->randomName();
    $this->assertSame($this->language, $this->language->setNegotiationMethodId($method_id));
    $this->assertSame($method_id, $this->language->getNegotiationMethodId());
  }

}
