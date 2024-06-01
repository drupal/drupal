<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Attribute\ViewsArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an entity reference ID value.
 *
 * This handler accepts entity reference ID values. The definition defines the
 * `target_entity_type_id` parameter to determine what kind of ID to load.
 * Entity ID values that are directly part of an entity are handled by
 * EntityArgument.
 *
 * @see \Drupal\views\Plugin\views\argument\EntityArgument
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'entity_target_id'
)]
class EntityReferenceArgument extends NumericArgument implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityRepositoryInterface $entityRepository,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function titleQuery() {
    $titles = [];

    $entities = $this->entityTypeManager->getStorage($this->definition['target_entity_type_id'])->loadMultiple($this->value);
    foreach ($entities as $entity) {
      $titles[$entity->id()] = $this->entityRepository->getTranslationFromContext($entity)->label();
    }
    return $titles;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryName($data) {
    $id = $data->{$this->name_alias};
    $entity = $id ? $this->entityTypeManager->getStorage($this->definition['target_entity_type_id'])->load($id) : NULL;
    if ($entity) {
      return $this->entityRepository->getTranslationFromContext($entity)->label();
    }
    if (($id === NULL || $id === '') && isset($this->definition['empty field name'])) {
      return $this->definition['empty field name'];
    }
    return $id;
  }

}
