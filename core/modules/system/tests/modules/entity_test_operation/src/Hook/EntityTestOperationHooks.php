<?php

declare(strict_types=1);

namespace Drupal\entity_test_operation\Hook;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_operation.
 */
class EntityTestOperationHooks {

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    return [
      'test' => [
        'title' => t('Front page'),
        'url' => Url::fromRoute('<front>'),
        'weight' => 0,
      ],
    ];
  }

}
