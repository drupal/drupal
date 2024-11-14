<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableInterface;

/**
 * A stub revisionable entity for testing purposes.
 */
class StubRevisionableEntity extends StubEntityBase implements RevisionableInterface {

  /**
   * {@inheritdoc}
   */
  public function isNewRevision(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($value = TRUE): void {
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionId(): int|string|NULL {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoadedRevisionId(): ?int {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLoadedRevisionId(): static {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision($new_value = NULL): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function wasDefaultRevision(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record): void {
  }

}
