<?php

declare(strict_types=1);

namespace Drupal\update\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\update\ProjectSecurityData;
use Drupal\update\ProjectSecurityRequirement;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\update\UpdateMessageTrait;

/**
 * Requirements for the update module.
 */
class UpdateRequirements {

  use StringTranslationTrait;
  use UpdateMessageTrait;

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly UpdateManagerInterface $updateManager,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   *
   * Describes the status of the site regarding available updates. If
   * there is no update data, only one record will be returned, indicating that
   * the status of core can't be determined. If data is available, there will
   * be two records: one for core, and another for all of contrib (assuming
   * there are any contributed modules or themes installed on the site). In
   * addition to the fields expected by hook_requirements ('value', 'severity',
   * and optionally 'description'), this array will contain a 'reason'
   * attribute, which is an integer constant to indicate why the given status
   * is being returned (UPDATE_NOT_SECURE, UPDATE_NOT_CURRENT, or
   * UPDATE_UNKNOWN). This is used for generating the appropriate email
   * notification messages during cron runs, and might be useful for other
   * modules to find out if the site is up to date or not.
   *
   * @see \Drupal\update\UpdateMessageTrait::getText()
   * @see \Drupal\update\Hook\UpdateCronHooks::notify()
   * @see \Drupal\update\UpdateManagerInterface
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    if ($available = $this->updateManager->getAvailable(FALSE)) {
      $this->moduleHandler->loadInclude('update', 'inc', 'update.compare');
      $data = $this->updateManager->calculateProjectData($available);
      // First, populate the requirements for core:
      $requirements['update_core'] = $this->requirementCheck($data['drupal'], 'core');
      if (!empty($available['drupal']['releases'])) {
        $security_data = ProjectSecurityData::createFromProjectDataAndReleases($data['drupal'], $available['drupal']['releases'])->getCoverageInfo();
        if ($core_coverage_requirement = ProjectSecurityRequirement::createFromProjectDataAndSecurityCoverageInfo($data['drupal'], $security_data)->getRequirement()) {
          $requirements['coverage_core'] = $core_coverage_requirement;
        }
      }

      // We don't want to check drupal a second time.
      unset($data['drupal']);
      if (!empty($data)) {
        // Now, sort our $data array based on each project's status. The
        // status constants are numbered in the right order of precedence, so
        // we just need to make sure the projects are sorted in ascending
        // order of status, and we can look at the first project we find.
        uasort($data, function ($a, $b) {
          // The status constants are numerically in the right order, so we can
          // usually subtract the two to compare in the order we want. However,
          // negative status values should be treated as if they are huge,
          // since we always want them at the bottom of the list.
          $a_status = $a['status'] > 0 ? $a['status'] : (-10 * $a['status']);
          $b_status = $b['status'] > 0 ? $b['status'] : (-10 * $b['status']);
          return $a_status - $b_status;
        });
        $first_project = reset($data);
        $requirements['update_contrib'] = $this->requirementCheck($first_project, 'contrib');
      }
    }
    else {
      $requirements['update_core']['title'] = $this->t('Drupal core update status');
      $requirements['update_core']['value'] = $this->t('No update data available');
      $requirements['update_core']['severity'] = RequirementSeverity::Warning;
      $requirements['update_core']['reason'] = UpdateFetcherInterface::UNKNOWN;
      $requirements['update_core']['description'] = $this->noData();
    }
    return $requirements;
  }

  /**
   * Fills in the requirements array.
   *
   * This is shared for both core and contrib to generate the right elements in
   * the array for hook_runtime_requirements().
   *
   * @param array $project
   *   Array of information about the project we're testing as returned by
   *   \Drupal\update\UpdateManagerInterface::calculateProjectData().
   * @param string $type
   *   What kind of project this is ('core' or 'contrib').
   *
   * @return array
   *   An array to be included in the nested $requirements array.
   *
   * @see hook_requirements()
   * @see Drupal\update\Hook\UpdateRequirements::runtime()
   * @see \Drupal\update\UpdateManagerInterface::calculateProjectData()
   */
  protected function requirementCheck($project, $type): array {
    $requirement = [];
    if ($type == 'core') {
      $requirement['title'] = $this->t('Drupal core update status');
    }
    else {
      $requirement['title'] = $this->t('Module and theme update status');
    }
    $status = $project['status'];
    if ($status != UpdateManagerInterface::CURRENT) {
      $requirement['reason'] = $status;
      $requirement['severity'] = RequirementSeverity::Error;
      $requirement['description'][] = ['#markup' => $this->getText($type, $status)];
      if (!in_array($status, [
        UpdateFetcherInterface::UNKNOWN,
        UpdateFetcherInterface::NOT_CHECKED,
        UpdateFetcherInterface::NOT_FETCHED,
        UpdateFetcherInterface::FETCH_PENDING,
      ])) {
        $url = Url::fromRoute('update.status');
        // When updates are available, if the current user has access to the
        // available updates report, append the available updates link to the
        // message from \Drupal\update\UpdateMessageTrait::getText(), and
        // format the two translated strings together in a single paragraph.
        if ($url->access()) {
          $requirement['description'][] = [
            '#prefix' => ' ',
            '#markup' => $this->t('See the <a href=":available_updates">available updates</a> page for more information.', [':available_updates' => $url->toString()]),
          ];
        }
      }
    }
    switch ($status) {
      case UpdateManagerInterface::NOT_SECURE:
        $requirement_label = $this->t('Not secure!');
        break;

      case UpdateManagerInterface::REVOKED:
        $requirement_label = $this->t('Revoked!');
        break;

      case UpdateManagerInterface::NOT_SUPPORTED:
        $requirement_label = $this->t('Unsupported release');
        break;

      case UpdateManagerInterface::NOT_CURRENT:
        $requirement_label = $this->t('Out of date');
        $requirement['severity'] = RequirementSeverity::Warning;
        break;

      case UpdateFetcherInterface::UNKNOWN:
      case UpdateFetcherInterface::NOT_CHECKED:
      case UpdateFetcherInterface::NOT_FETCHED:
      case UpdateFetcherInterface::FETCH_PENDING:
        $requirement_label = $project['reason'] ?? $this->t('Can not determine status');
        $requirement['severity'] = RequirementSeverity::Warning;
        break;

      default:
        $requirement_label = $this->t('Up to date');
    }
    if ($status != UpdateManagerInterface::CURRENT && $type == 'core' && isset($project['recommended'])) {
      $requirement_label .= ' ' . $this->t('(version @version available)', ['@version' => $project['recommended']]);
    }
    $requirement['value'] = Link::fromTextAndUrl($requirement_label, Url::fromRoute('update.status'))->toString();
    return $requirement;
  }

}
