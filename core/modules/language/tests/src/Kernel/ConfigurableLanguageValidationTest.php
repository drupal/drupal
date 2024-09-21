<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests validation of configurable_language entities.
 *
 * @group language
 */
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
