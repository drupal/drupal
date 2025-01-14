<?php

namespace Drupal\content_moderation\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\ViewsData;

/**
 * Hook implementations for content_moderation.
 */
class ContentModerationViewsHooks {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModerationInformationInterface $moderationInformation,
  ) {}

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $viewsData = new ViewsData(
      $this->entityTypeManager,
      $this->moderationInformation
    );
    return $viewsData->getViewsData();
  }

}
