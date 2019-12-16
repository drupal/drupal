<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A utility class to make updating configuration entities simple.
 *
 * Use this in a post update function like so:
 * @code
 * // Ensure Taxonomy module installed before trying to update vocabularies.
 * if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
 *   // Update the dependencies of all Vocabulary configuration entities.
 *   \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'taxonomy_vocabulary');
 * }
 * @endcode
 *
 * The number of entities processed in each batch is determined by the
 * 'entity_update_batch_size' setting.
 *
 * @see default.settings.php
 */
class ConfigEntityUpdater implements ContainerInjectionInterface {

  /**
   * The key used to store information in the update sandbox.
   */
  const SANDBOX_KEY = 'config_entity_updater';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The number of entities to process in each batch.
   * @var int
   */
  protected $batchSize;

  /**
   * ConfigEntityUpdater constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param int $batch_size
   *   The number of entities to process in each batch.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $batch_size) {
    $this->entityTypeManager = $entity_type_manager;
    $this->batchSize = $batch_size;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('settings')->get('entity_update_batch_size', 50)
    );
  }

  /**
   * Updates configuration entities as part of a Drupal update.
   *
   * @param array $sandbox
   *   Stores information for batch updates.
   * @param string $entity_type_id
   *   The configuration entity type ID. For example, 'view' or 'vocabulary'.
   *   The calling code should ensure that the entity type exists beforehand
   *   (i.e., by checking that the entity type is defined or that the module
   *   that provides it is installed).
   * @param callable $callback
   *   (optional) A callback to determine if a configuration entity should be
   *   saved. The callback will be passed each entity of the provided type that
   *   exists. The callback should not save an entity itself. Return TRUE to
   *   save an entity. The callback can make changes to an entity. Note that all
   *   changes should comply with schema as an entity's data will not be
   *   validated against schema on save to avoid unexpected errors. If a
   *   callback is not provided, the default behaviour is to update the
   *   dependencies if required.
   *
   * @see hook_post_update_NAME()
   *
   * @api
   *
   * @throws \InvalidArgumentException
   *   Thrown when the provided entity type ID is not a configuration entity
   *   type.
   * @throws \RuntimeException
   *   Thrown when used twice in the same update function for different entity
   *   types. This method should only be called once per update function.
   */
  public function update(array &$sandbox, $entity_type_id, callable $callback = NULL) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    if (isset($sandbox[self::SANDBOX_KEY]) && $sandbox[self::SANDBOX_KEY]['entity_type'] !== $entity_type_id) {
      throw new \RuntimeException('Updating multiple entity types in the same update function is not supported');
    }
    if (!isset($sandbox[self::SANDBOX_KEY])) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if (!($entity_type instanceof ConfigEntityTypeInterface)) {
        throw new \InvalidArgumentException("The provided entity type ID '$entity_type_id' is not a configuration entity type");
      }
      $sandbox[self::SANDBOX_KEY]['entity_type'] = $entity_type_id;
      $sandbox[self::SANDBOX_KEY]['entities'] = $storage->getQuery()->accessCheck(FALSE)->execute();
      $sandbox[self::SANDBOX_KEY]['count'] = count($sandbox[self::SANDBOX_KEY]['entities']);
    }

    // The default behaviour is to fix dependencies.
    if ($callback === NULL) {
      $callback = function ($entity) {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
        $original_dependencies = $entity->getDependencies();
        return $original_dependencies !== $entity->calculateDependencies()->getDependencies();
      };
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entities = $storage->loadMultiple(array_splice($sandbox[self::SANDBOX_KEY]['entities'], 0, $this->batchSize));
    foreach ($entities as $entity) {
      if (call_user_func($callback, $entity)) {
        $entity->trustData();
        $entity->save();
      }
    }

    $sandbox['#finished'] = empty($sandbox[self::SANDBOX_KEY]['entities']) ? 1 : ($sandbox[self::SANDBOX_KEY]['count'] - count($sandbox[self::SANDBOX_KEY]['entities'])) / $sandbox[self::SANDBOX_KEY]['count'];
  }

}
