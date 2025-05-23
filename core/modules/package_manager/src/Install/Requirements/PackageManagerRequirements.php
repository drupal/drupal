<?php

declare(strict_types=1);

namespace Drupal\package_manager\Install\Requirements;

use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Site\Settings;
use Drupal\package_manager\Exception\FailureMarkerExistsException;
use Drupal\package_manager\FailureMarker;

/**
 * Install time requirements for the package_manager module.
 */
class PackageManagerRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];

    if (Settings::get('testing_package_manager', FALSE) === FALSE) {
      $requirements['testing_package_manager'] = [
        'title' => 'Package Manager',
        'description' => t("Package Manager is available for early testing. To install the module set the value of 'testing_package_manager' to TRUE in your settings.php file."),
        'severity' => RequirementSeverity::Error,
      ];
    }

    // If we're able to check for the presence of the failure marker at all, do
    // it irrespective of the current run phase. If the failure marker is there,
    // the site is in an indeterminate state and should be restored from backup
    // ASAP.
    $service_id = FailureMarker::class;
    if (\Drupal::hasService($service_id)) {
      try {
        \Drupal::service($service_id)->assertNotExists(NULL);
      }
      catch (FailureMarkerExistsException $exception) {
        $requirements['package_manager_failure_marker'] = [
          'title' => t('Failed Package Manager update detected'),
          'description' => $exception->getMessage(),
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    return $requirements;
  }

}
