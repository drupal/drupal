<?php

declare(strict_types=1);

namespace Drupal\update_test_schema\Hook;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;

/**
 * Requirements for the Update Test Schema module.
 */
class UpdateTestSchemaRequirements {

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $requirements['path_alias_test'] = [
      'title' => 'Path alias test',
      'value' => 'Check a path alias for the admin page',
      'severity' => RequirementSeverity::Info,
      'description' => new FormattableMarkup('Visit <a href=":link">the structure page</a> to do many useful things.', [
        ':link' => Url::fromRoute('system.admin_structure')->toString(),
      ]),
    ];
    return $requirements;
  }

}
