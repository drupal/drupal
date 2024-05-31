<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Attribute\ViewsArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept an entity ID value.
 *
 * This handler accepts the identifiers of entities themselves. The definition
 * defines the `entity_type` parameter to determine what kind of ID to load.
 * Entity reference ID values are handled by EntityReferenceArgument.
 *
 * @see \Drupal\views\Plugin\views\argument\EntityReferenceArgument
 *
 * @ingroup views_argument_handlers
 */
#[ViewsArgument(
  id: 'entity_id',
)]
class EntityArgument extends NumericArgument implements ContainerFactoryPluginInterface {

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

    $entities = $this->entityTypeManager->getStorage($this->definition['entity_type'])->loadMultiple($this->value);
    foreach ($entities as $entity) {
      $titles[$entity->id()] = $this->entityRepository->getTranslationFromContext($entity)->label();
    }
    return $titles;
  }

}
