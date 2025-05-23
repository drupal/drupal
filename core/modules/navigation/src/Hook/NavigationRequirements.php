<?php

declare(strict_types=1);

namespace Drupal\navigation\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the navigation module.
 */
class NavigationRequirements {

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
    if ($this->moduleHandler->moduleExists('toolbar')) {
      $requirements['toolbar'] = [
        'title' => $this->t('Toolbar and Navigation modules are both installed'),
        'value' => $this->t('The Navigation module is a complete replacement for the Toolbar module and disables its functionality when both modules are installed. If you are planning to continue using Navigation module, you can uninstall the Toolbar module now.'),
        'severity' => RequirementSeverity::Warning,
      ];
    }
    return $requirements;
  }

}
