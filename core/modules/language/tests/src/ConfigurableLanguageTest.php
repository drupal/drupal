<?php
/**
 * @file
 * Contains \Drupal\Tests\language\ConfigurableLanguageTest.
 */

namespace Drupal\Tests\language;

use Drupal\simpletest\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the ConfigurableLanguage entity.
 *
 * @group language
 * @see \Drupal\language\Entity\ConfigurableLanguage.
 */
class ConfigurableLanguageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  /**
   * Tests configurable language name methods.
   */
  public function testName() {
    $name = $this->randomMachineName();
    $language_code = $this->randomMachineName(2);
    $configurableLanguage = new ConfigurableLanguage(array('label' => $name, 'id' => $language_code), 'configurable_language');
    $this->assertEqual($configurableLanguage->getName(), $name);
    $this->assertEqual($configurableLanguage->setName('Test language')->getName(), 'Test language');
  }

}
