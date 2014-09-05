<?php
/**
 * @file
 * Contains \Drupal\Tests\language\Unit\ConfigurableLanguageUnitTest.
 */

namespace Drupal\Tests\language\Unit;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConfigurableLanguage entity class.
 *
 * @group language
 * @coversDefaultClass \Drupal\language\Entity\ConfigurableLanguage
 * @see \Drupal\language\Entity\ConfigurableLanguage.
 */
class ConfigurableLanguageUnitTest extends UnitTestCase {

  /**
   * The Entity under test.
   *
   * @var \Drupal\language\Entity\ConfigurableLanguage;
   */
  protected $configurableLanguage;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configurableLanguage = new ConfigurableLanguage(array(), 'configurable_language');
  }

  /**
   * @covers ::setDirection
   * @covers ::getDirection
   */
  public function testDirection() {
    // Direction of language writing, an integer. Usually either
    // ConfigurableLanguage::DIRECTION_LTR or
    // ConfigurableLanguage::DIRECTION_RTL.
    $set_direction_ltr = ConfigurableLanguage::DIRECTION_LTR;
    $this->configurableLanguage->setDirection($set_direction_ltr);
    $this->assertEquals($this->configurableLanguage->getDirection(), $set_direction_ltr, 'LTR direction written and read correctly.');

    // Test direction again, setting direction to RTL.
    $set_direction_rtl = ConfigurableLanguage::DIRECTION_RTL;
    $this->configurableLanguage->setDirection($set_direction_rtl);
    $this->assertEquals($this->configurableLanguage->getDirection(), $set_direction_rtl, 'RTL direction written and read correctly.');
  }

  /**
   * @covers ::setWeight
   * @covers ::getWeight
   */
  public function testWeight() {
    // The weight, an integer. Used to order languages with larger positive
    // weights sinking items toward the bottom of lists.
    $set_weight = -5;
    $this->configurableLanguage->setWeight($set_weight);
    $this->assertEquals($this->configurableLanguage->getWeight(), $set_weight, 'Weight written and read correctly.');
  }

  /**
   * @covers::setNegotiationMethodId
   * @covers::setNegotiationMethodId
   */
  public function testNegotiationMethodId(){
    // Language's negotiation method ID, a string. E.g.
    // \Drupal\language\LanguageNegotiatorInterface::METHOD_ID.
    $set_method_id = 'language-foomethod';
    $this->configurableLanguage->setNegotiationMethodId($set_method_id);
    $this->assertEquals($this->configurableLanguage->getNegotiationMethodId(), $set_method_id, 'Negotiation Method Identifier written and read correctly.');
  }

}
