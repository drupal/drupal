<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ConfigurableLanguage entity.
 *
 * @see \Drupal\language\Entity\ConfigurableLanguage.
 */
#[Group('language')]
#[RunTestsInSeparateProcesses]
class ConfigurableLanguageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
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
