<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of configurable_language entities.
 */
#[Group('language')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class ConfigurableLanguageValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = ConfigurableLanguage::createFromLangcode('fr');
    $this->entity->save();
  }

}
