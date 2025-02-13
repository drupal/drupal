<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_non_cacheable_methods\Hook;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for jsonapi_test_non_cacheable_methods.
 */
class JsonapiTestNonCacheableMethodsHooks {

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity): void {
    Url::fromRoute('<front>')->toString();
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    Url::fromRoute('<front>')->toString();
  }

}
