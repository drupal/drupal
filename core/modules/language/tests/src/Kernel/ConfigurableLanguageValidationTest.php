<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of configurable_language entities.
 */
#[Group('language')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class ConfigurableLanguageValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->entity = ConfigurableLanguage::createFromLangcode('fr');
    $this->entity->save();
  }

}
