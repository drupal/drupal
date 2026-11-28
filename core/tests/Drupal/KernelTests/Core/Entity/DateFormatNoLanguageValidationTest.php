<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of date_format entities without the language module.
 *
 * The other config entity validation tests run with the language module, which
 * replaces the language manager. This test ensures validation also works with
 * the core language manager, as on sites without the language module.
 */
#[Group('Entity')]
#[Group('Validation')]
#[Group('config')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
class DateFormatNoLanguageValidationTest extends DateFormatValidationTest {

  /**
   * {@inheritdoc}
   */
  protected static function getModulesToEnable($class): array {
    return array_values(array_diff(parent::getModulesToEnable($class), ['language']));
  }

  /**
   * Tests that the language module is not enabled.
   *
   * Configurable languages need the language module, so there is nothing to
   * validate.
   */
  public function testConfigurableLanguage(): void {
    $this->assertFalse($this->container->get('module_handler')->moduleExists('language'));
  }

}
