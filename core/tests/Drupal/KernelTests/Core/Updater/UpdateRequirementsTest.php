<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Updater;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests hook_update_requirements() and hook_update_requirements_alter().
 *
 * @group Hooks
 */
class UpdateRequirementsTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * Tests hook_update_requirements().
   */
  public function testUpdateRequirements(): void {
    require_once 'core/includes/update.inc';

    \Drupal::service('module_installer')->install(['module_update_requirements']);
    $testRequirements = [
      'title' => $this->t('UpdateError'),
      'value' => $this->t('None'),
      'description' => $this->t('Update Error.'),
      'severity' => REQUIREMENT_ERROR,
    ];
    $requirements = update_check_requirements()['test.update.error'];
    $this->assertEquals($testRequirements, $requirements);

    $testAlterRequirements = [
      'title' => $this->t('UpdateWarning'),
      'value' => $this->t('None'),
      'description' => $this->t('Update Warning.'),
      'severity' => REQUIREMENT_WARNING,
    ];
    $alterRequirements = update_check_requirements()['test.update.error.alter'];
    $this->assertEquals($testAlterRequirements, $alterRequirements);
  }

}
