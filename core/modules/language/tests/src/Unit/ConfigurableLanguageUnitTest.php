<?php

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
   * @covers ::getDirection
   */
  public function testDirection() {
    // Direction of language writing, an integer. Usually either
    // ConfigurableLanguage::DIRECTION_LTR or
    // ConfigurableLanguage::DIRECTION_RTL.
    $configurableLanguage = new ConfigurableLanguage(['direction' => ConfigurableLanguage::DIRECTION_LTR], 'configurable_language');
    $this->assertEquals(ConfigurableLanguage::DIRECTION_LTR, $configurableLanguage->getDirection());

    // Test direction again, setting direction to RTL.
    $configurableLanguage = new ConfigurableLanguage(['direction' => ConfigurableLanguage::DIRECTION_RTL], 'configurable_language');
    $this->assertEquals(ConfigurableLanguage::DIRECTION_RTL, $configurableLanguage->getDirection());
  }

  /**
   * @covers ::getWeight
   * @covers ::setWeight
   */
  public function testWeight() {
    // The weight, an integer. Used to order languages with larger positive
    // weights sinking items toward the bottom of lists.
    $configurableLanguage = new ConfigurableLanguage(['weight' => -5], 'configurable_language');
    $this->assertEquals(-5, $configurableLanguage->getWeight());
    $this->assertEquals(13, $configurableLanguage->setWeight(13)->getWeight());
  }

}
