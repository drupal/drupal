<?php

namespace Drupal\package_manager\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Site\Settings;
use Drupal\package_manager\ComposerInspector;
use Drupal\package_manager\Exception\FailureMarkerExistsException;
use Drupal\package_manager\FailureMarker;
use PhpTuf\ComposerStager\API\Exception\ExceptionInterface;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * Requirements checks for Package Manager.
 */
class PackageManagerRequirementsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ComposerInspector $composerInspector,
    protected ExecutableFinderInterface $executableFinder,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $requirements = $this->checkSettings($requirements);
    $requirements = $this->checkFailure($requirements);

    // Report the Composer version in use, as well as its path.
    $title = $this->t('Composer version');
    try {
      $requirements['package_manager_composer'] = [
        'title' => $title,
        'description' => $this->t('@version (<code>@path</code>)', [
          '@version' => $this->composerInspector->getVersion(),
          '@path' => $this->executableFinder->find('composer'),
        ]),
        'severity' => RequirementSeverity::Info,
      ];
    }
    catch (\Throwable $e) {
      // All Composer Stager exceptions are translatable.
      $message = $e instanceof ExceptionInterface
        ? $e->getTranslatableMessage()
        : $e->getMessage();

      $requirements['package_manager_composer'] = [
        'title' => $title,
        'description' => $this->t('Composer was not found. The error message was: @message', [
          '@message' => $message,
        ]),
        'severity' => RequirementSeverity::Error,
      ];
    }

    return $requirements;
  }

  /**
   * Implements hook_update_requirements().
   */
  #[Hook('update_requirements')]
  public function update(): array {
    $requirements = [];
    $requirements = $this->checkSettings($requirements);
    $requirements = $this->checkFailure($requirements);
    return $requirements;
  }

  /**
   * Check that package manager has an explicit setting to allow installation.
   *
   * @param array $requirements
   *   The requirements array that has been processed so far.
   *
   * @return array
   *   Requirements array.
   *
   * @see hook_runtime_requirements
   * @see hook_update_requirements
   */
  public function checkSettings($requirements): array {
    if (Settings::get('testing_package_manager', FALSE) === FALSE) {
      $requirements['testing_package_manager'] = [
        'title' => 'Package Manager',
        'description' => $this->t("Package Manager is available for early testing. To install the module set the value of 'testing_package_manager' to TRUE in your settings.php file."),
        'severity' => RequirementSeverity::Error,
      ];
    }

    return $requirements;
  }

  /**
   * Check for a failed update.
   *
   * This is run during requirements to allow restoring from backup.
   *
   * @param array $requirements
   *   The requirements array that has been processed so far.
   *
   * @return array
   *   Requirements array.
   *
   * @see hook_runtime_requirements
   * @see hook_update_requirements
   */
  public function checkFailure(array $requirements): array {
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
          'title' => $this->t('Failed Package Manager update detected'),
          'description' => $exception->getMessage(),
          'severity' => RequirementSeverity::Error,
        ];
      }
    }

    return $requirements;
  }

}
