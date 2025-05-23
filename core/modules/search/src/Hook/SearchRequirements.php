<?php

declare(strict_types=1);

namespace Drupal\search\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search\SearchPageRepositoryInterface;

/**
 * Requirements for the Search module.
 */
class SearchRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly SearchPageRepositoryInterface $searchPageRepository,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   *
   * For the Status Report, return information about search index status.
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $remaining = 0;
    $total = 0;
    foreach ($this->searchPageRepository->getIndexableSearchPages() as $entity) {
      $status = $entity->getPlugin()->indexStatus();
      $remaining += $status['remaining'];
      $total += $status['total'];
    }

    $done = $total - $remaining;
    // Use floor() to calculate the percentage, so if it is not quite 100%, it
    // will show as 99%, to indicate "almost done".
    $percent = ($total > 0 ? floor(100 * $done / $total) : 100);
    $requirements['search_status'] = [
      'title' => $this->t('Search index progress'),
      'value' => $this->t('@percent% (@remaining remaining)', ['@percent' => $percent, '@remaining' => $remaining]),
      'severity' => RequirementSeverity::Info,
    ];
    return $requirements;
  }

}
