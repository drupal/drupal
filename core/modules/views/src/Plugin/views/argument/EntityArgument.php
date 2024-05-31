<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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

  protected EntityRepositoryInterface $entityRepository;

  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityRepositoryInterface | EntityStorageInterface | EntityTypeManagerInterface $entityRepository,
    ?EntityTypeManagerInterface $entityTypeManager = NULL,
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (!$entityRepository instanceof EntityRepositoryInterface) {
      @trigger_error('Passing either \Drupal\Core\Entity\EntityStorageInterface or \Drupal\Core\Entity\EntityTypeManagerInterface to ' . __METHOD__ . '() as argument 4 is deprecated in drupal:10.3.0 and will be removed before drupal:11.0.0. Pass a Drupal\Core\Entity\EntityRepositoryInterface instead. See https://www.drupal.org/node/3441945', E_USER_DEPRECATED);
      $entityRepository = \Drupal::service('entity.repository');
    }
    $this->entityRepository = $entityRepository;

    if ($entityTypeManager === NULL) {
      @trigger_error('Not passing the \Drupal\Core\Entity\EntityTypeManagerInterface to ' . __METHOD__ . '() as argument 5 is deprecated in drupal:10.3.0 and will be required before drupal:11.0.0. See https://www.drupal.org/node/3441945', E_USER_DEPRECATED);
      $entityTypeManager = \Drupal::service('entity_type.manager');
    }
    $this->entityTypeManager = $entityTypeManager;

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

  /**
   * Array of deprecated storage properties that legacy classes might access.
   *
   * This class is replacing many separate plugins from different core modules,
   * each of which had a storage property for their own entity type. We can't
   * use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait since
   * these are not registered as services, but are the storage "sub-service"
   * from the entityTypeManager for each entity type.
   */
  protected array $deprecatedStorageProperties = [
    'nodeStorage' => 'node',
    'termStorage' => 'taxonomy_term',
    'vocabularyStorage' => 'taxonomy_vocabulary',
    'storage' => 'user',
  ];

  /**
   * Allows to access deprecated/removed properties.
   *
   * This method must be public.
   */
  public function __get($name) {
    if (isset($this->deprecatedStorageProperties[$name])) {
      $storage_name = $this->deprecatedStorageProperties[$name];
      $class_name = static::class;
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error("The property $name ($storage_name storage service) is deprecated in $class_name and will be removed before Drupal 11.0.0. See https://www.drupal.org/node/3441945", E_USER_DEPRECATED);
      return $this->entityTypeManager->getStorage($storage_name);
    }
  }

}
