<?php

declare(strict_types=1);

namespace Drupal\toolbar\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the navigation module.
 */
class ToolbarRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    if ($this->moduleHandler->moduleExists('navigation')) {
      $requirements['navigation'] = [
        'title' => $this->t('Toolbar and Navigation modules are both installed'),
        'value' => $this->t('Toolbar is disabled when the Navigation replacement module is available to the current user. It is recommended to uninstall Toolbar.'),
        'severity' => RequirementSeverity::Warning,
      ];
    }
    return $requirements;
  }

}
