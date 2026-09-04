<?php

declare(strict_types=1);

namespace Drupal\update;

/**
 * Update project information.
 *
 * Update information for one project. This is used to assist with calculating
 * the relevant updates and version compatibility.
 *
 * @see \Drupal\update\UpdateCalculator
 */
class UpdateProject extends \ArrayObject {

  /**
   * A value object to track available updates.
   *
   * @param string $name
   *   The name of the project.
   * @param array $info
   *   The content of the info yml.
   * @param array|null $includes
   *   The modules or themes included in this project.
   * @param string|null $projectType
   *   The project type:
   *     - module
   *     - theme
   *     - uninstalled-module
   *     - uninstalled-theme.
   * @param array|null $disabled
   *   The modules that are disabled.
   * @param string|int|null $existingMajor
   *   The existing major.
   * @param int|string|null $status
   *   One of:
   *    - UpdateManagerInterface::NOT_SECURE
   *    - UpdateManagerInterface::REVOKED
   *    - UpdateManagerInterface::NOT_SUPPORTED
   *    - UpdateManagerInterface::NOT_CURRENT
   *    - UpdateManagerInterface::CURRENT.
   * @param array $extra
   *   The extra information about the module.
   * @param string|null $title
   *   The project label.
   * @param string|null $link
   *   The link to the project.
   * @param string|null $reason
   *   The reason for the status.
   * @param string|bool|null $projectStatus
   *   The status of the available update.
   * @param string|\Stringable $existingVersion
   *   The current version.
   * @param int|null $fetchStatus
   *   One of:
   *    - UpdateFetcherInterface::NOT_CHECKED
   *    - UpdateFetcherInterface::UNKNOWN
   *    - UpdateFetcherInterface::NOT_FETCHED
   *    - UpdateFetcherInterface::FETCH_PENDING.
   * @param string|null $recommended
   *   The recommended version.
   * @param array $also
   *   The also available information.
   * @param array $releases
   *   All releases for this project.
   * @param string|null $latestVersion
   *   The newest version available.
   * @param string|null $devVersion
   *   Any dev versions available.
   * @param string|null $installType
   *   The type of install e.g. 'official'.
   * @param string|null $latestDev
   *   The most recent dev version.
   * @param int|string|null $datestamp
   *   The datestamp of the release.
   * @param array|null $securityUpdates
   *   Any security updates available.
   */
  public function __construct(
    public string $name,
    public array $info,
    public ?array $includes = NULL,
    public ?string $projectType = NULL,
    public ?array $disabled = NULL,
    public string|int|null $existingMajor = NULL,
    public int|string|null $status = NULL,
    public array $extra = [],
    public ?string $title = NULL,
    public ?string $link = NULL,
    public ?string $reason = NULL,
    public string|bool|null $projectStatus = NULL,
    public string|\Stringable $existingVersion = '',
    public ?int $fetchStatus = NULL,
    public ?string $recommended = NULL,
    public array $also = [],
    public array $releases = [],
    public ?string $latestVersion = NULL,
    public ?string $devVersion = NULL,
    public ?string $installType = NULL,
    public ?string $latestDev = NULL,
    public int|string|null $datestamp = NULL,
    public ?array $securityUpdates = NULL,
  ) {}

  /**
   * Creates a UpdateProject object.
   *
   * @param array $data
   *   The project data from the Update XML.
   *
   * @return static
   */
  public static function createFromArray(array $data): static {
    $projectData = [];

    // Convert from snake case to properties.
    foreach ($data as $key => $value) {
      switch ($key) {
        case 'fetch_status':
          $projectData['fetchStatus'] = $value;
          break;

        case 'install_type':
          $projectData['installType'] = $value;
          break;

        case 'latest_dev':
          $projectData['latestDev'] = $value;
          break;

        case 'latest_version':
          $projectData['latestVersion'] = $value;
          break;

        case 'project_type':
          $projectData['projectType'] = $value;
          break;

        case 'existing_major':
          $projectData['existingMajor'] = $value;
          break;

        case 'project_status':
          $projectData['projectStatus'] = $value;
          break;

        case 'existing_version':
          $projectData['existingVersion'] = $value;
          break;

        case 'security updates':
          $projectData['securityUpdates'] = $value;
          break;

        default:
          if ($value) {
            $projectData[$key] = $value;
          }
          break;

      }
    }
    return new UpdateProject(...$projectData);
  }

  /**
   * Creates an array from UpdateProject object.
   *
   * @return array
   *   The array representation on the object.
   */
  public function toArray(): array {
    $projectData = [];

    // Convert to snake case from properties.
    foreach (get_object_vars($this) as $property => $value) {
      switch ($property) {
        case 'fetchStatus':
          if ($value) {
            $projectData['fetch_status'] = $value;
          }
          break;

        case 'installType':
          if ($value) {
            $projectData['install_type'] = $value;
          }
          break;

        case 'latestDev':
          if ($value) {
            $projectData['latest_dev'] = $value;
          }
          break;

        case 'latestVersion':
          if ($value) {
            $projectData['latest_version'] = $value;
          }
          break;

        case 'projectType':
          if ($value) {
            $projectData['project_type'] = $value;
          }
          break;

        case 'existingMajor':
          if ($value) {
            $projectData['existing_major'] = $value;
          }
          break;

        case 'projectStatus':
          if ($value) {
            $projectData['project_status'] = $value;
          }
          break;

        case 'securityUpdates':
          if ($value) {
            $projectData['security updates'] = $value;
          }
          break;

        case 'existingVersion':
          // Ensure we do not get a property that is pascal case set as a key.
          break;

        default:
          if ($value) {
            $projectData[$property] = $value;
          }
          break;

      }
    }

    // This key must always exist even if the prop does not.
    $projectData['existing_version'] = $this->existingVersion;

    return $projectData;
  }

}
