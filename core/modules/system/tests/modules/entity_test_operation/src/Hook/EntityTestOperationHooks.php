<?php

declare(strict_types=1);

namespace Drupal\entity_test_operation\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_test_operation.
 */
class EntityTestOperationHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    return [
      'test' => [
        'title' => $this->t('Front page'),
        'url' => Url::fromRoute('<front>'),
        'weight' => 0,
      ],
    ];
  }

}
