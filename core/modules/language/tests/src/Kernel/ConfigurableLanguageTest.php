<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\KernelTestBase;
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
  protected static $modules = ['language'];

  /**
   * Tests configurable language name methods.
   */
  public function testName(): void {
    $name = $this->randomMachineName();
    $language_code = $this->randomMachineName(2);
    $configurableLanguage = new ConfigurableLanguage(['label' => $name, 'id' => $language_code], 'configurable_language');
    $this->assertEquals($name, $configurableLanguage->getName());
    $this->assertEquals('Test language', $configurableLanguage->setName('Test language')->getName());
  }

}
