<?php

namespace Drupal\content_moderation;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\node\NodeListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of moderated node entities.
 */
class ModeratedNodeListBuilder extends NodeListBuilder {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\RevisionableStorageInterface
   */
  protected $storage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ModeratedNodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $storage, $date_formatter, $redirect_destination);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $revision_ids = $this->getEntityRevisionIds();
    return $this->storage->loadMultipleRevisions($revision_ids);
  }

  /**
   * Loads entity revision IDs using a pager sorted by the entity revision ID.
   *
   * @return array
   *   An array of entity revision IDs.
   */
  protected function getEntityRevisionIds() {
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getAggregateQuery()
      ->accessCheck(TRUE)
      ->aggregate('content_entity_id', 'MAX')
      ->groupBy('content_entity_revision_id')
      ->condition('content_entity_type_id', $this->entityTypeId)
      ->condition('moderation_state', 'published', '<>')
      ->sort('content_entity_revision_id', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    $result = $query->execute();

    return $result ? array_column($result, 'content_entity_revision_id') : [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();
    $header['status'] = $this->t('Moderation state');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    $row['status'] = $entity->moderation_state->value;

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There is no moderated @label yet. Only pending versions of @label, such as drafts, are listed here.', ['@label' => $this->entityType->getLabel()]);

    return $build;
  }

}
