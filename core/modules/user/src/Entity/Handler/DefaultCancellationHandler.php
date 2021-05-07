<?php

namespace Drupal\user\Entity\Handler;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\CancellationHandlerInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an entity handler to react to user account cancellation.
 */
class DefaultCancellationHandler implements CancellationHandlerInterface, EntityHandlerInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity storage handler.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * DefaultCancellationHandler constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage
   *   The entity storage handler.
   */
  public function __construct(ContentEntityTypeInterface $entity_type, ContentEntityStorageInterface $storage) {
    $this->entityType = $entity_type;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cancelAccount(UserInterface $account, string $method): void {
    $ids = $this->getQuery($account, $method)->execute();
    $this->updateMultiple(array_keys($ids), $method);
  }

  /**
   * Begins updating entities in response to user account cancellation.
   *
   * @param array $ids
   *   The entity IDs, or revision IDs, to update.
   * @param string $method
   *   The cancellation method.
   */
  protected function updateMultiple(array $ids, string $method): void {
    $entities = $this->entityType->isRevisionable()
      ? $this->storage->loadMultipleRevisions($ids)
      : $this->storage->loadMultiple($ids);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $entity) {
      $this->updateEntity($entity, $method);
    }
    $this->messenger()->addStatus($this->t('The update has been performed.'));
  }

  /**
   * Builds an entity query to find the entities to update.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account being cancelled.
   * @param string $method
   *   The cancellation method.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query.
   */
  protected function getQuery(UserInterface $account, string $method): QueryInterface {
    $query = $this->storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($this->entityType->getKey('owner'), $account->id());

    if ($method === static::METHOD_REASSIGN && $this->entityType->isRevisionable()) {
      $query->allRevisions();
    }
    return $query;
  }

  /**
   * Updates a single entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to update.
   * @param string $method
   *   The user account cancellation method.
   */
  protected function updateEntity(ContentEntityInterface $entity, string $method): void {
    // For efficiency, manually save the original entity before applying any
    // changes.
    $entity->original = clone $entity;

    // Update each translation individually.
    $langcodes = array_keys($entity->getTranslationLanguages());
    foreach ($langcodes as $langcode) {
      $translation = $entity->getTranslation($langcode);

      if ($method === self::METHOD_BLOCK_UNPUBLISH) {
        $this->unpublish($translation);
      }
      elseif ($method === self::METHOD_REASSIGN) {
        $this->anonymize($translation);
      }
    }
    $this->storage->save($entity);
  }

  /**
   * Marks an entity as unpublished.
   *
   * Normally this is done for the self::METHOD_BLOCK_UNPUBLISH cancellation
   * method.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to unpublish.
   */
  protected function unpublish(ContentEntityInterface $entity): void {
    if ($entity instanceof EntityPublishedInterface) {
      $entity->setUnpublished();
    }
  }

  /**
   * Reassigns an entity to the anonymous user.
   *
   * Normally this is done for the self::METHOD_REASSIGN cancellation method.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to reassign to the anonymous user.
   */
  protected function anonymize(ContentEntityInterface $entity): void {
    if ($entity instanceof EntityOwnerInterface) {
      $entity->setOwnerId(0);
    }
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionUserId(0);
    }
  }

}
