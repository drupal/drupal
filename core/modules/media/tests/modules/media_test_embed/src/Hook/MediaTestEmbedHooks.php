<?php

declare(strict_types=1);

namespace Drupal\media_test_embed\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for media_test_embed.
 */
class MediaTestEmbedHooks {

  /**
   * Implements hook_entity_view_alter().
   */
  #[Hook('entity_view_alter')]
  public function entityViewAlter(&$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    $build['#attributes']['data-media-embed-test-active-theme'] = \Drupal::theme()->getActiveTheme()->getName();
    $build['#attributes']['data-media-embed-test-view-mode'] = $display->getMode();
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    return AccessResult::neutral()->addCacheTags([
      '_media_test_embed_filter_access:' . $entity->getEntityTypeId() . ':' . $entity->id(),
    ]);
  }

}
