<?php

declare(strict_types=1);

namespace Drupal\workspaces\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

/**
 * Install time requirements for the workspaces module.
 */
class WorkspacesRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    if (\Drupal::moduleHandler()->moduleExists('workspace')) {
      $requirements['workspace_incompatibility'] = [
        'severity' => RequirementSeverity::Error,
        'description' => t('Workspaces can not be installed when the contributed Workspace module is also installed. See the <a href=":link">upgrade path</a> page for more information on how to upgrade.', [
          ':link' => 'https://www.drupal.org/node/2987783',
        ]),
      ];
    }

    return $requirements;
  }

}
