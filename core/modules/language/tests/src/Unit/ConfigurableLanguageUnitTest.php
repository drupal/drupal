<?php
/**
 * @file
 * Contains \Drupal\Tests\language\Unit\ConfigurableLanguageUnitTest.
 */

namespace Drupal\Tests\language\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Language\Language;
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
    $configurableLanguage = new ConfigurableLanguage(array('direction' => ConfigurableLanguage::DIRECTION_LTR), 'configurable_language');
    $this->assertEquals(ConfigurableLanguage::DIRECTION_LTR, $configurableLanguage->getDirection());

    // Test direction again, setting direction to RTL.
    $configurableLanguage = new ConfigurableLanguage(array('direction' => ConfigurableLanguage::DIRECTION_RTL), 'configurable_language');
    $this->assertEquals(ConfigurableLanguage::DIRECTION_RTL, $configurableLanguage->getDirection());
  }

  /**
   * @covers ::getWeight
   */
  public function testWeight() {
    // The weight, an integer. Used to order languages with larger positive
    // weights sinking items toward the bottom of lists.
    $weight = -5;
    $configurableLanguage = new ConfigurableLanguage(array('weight' => $weight), 'configurable_language');
    $this->assertEquals($configurableLanguage->getWeight(), $weight);
  }

}
