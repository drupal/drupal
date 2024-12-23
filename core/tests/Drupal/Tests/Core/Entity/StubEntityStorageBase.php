<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A stub base entity storage for testing purposes.
 */
class StubEntityStorageBase extends EntityStorageBase {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(?array $ids = NULL): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities): void {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName(): string {
    return '';
  }

}
