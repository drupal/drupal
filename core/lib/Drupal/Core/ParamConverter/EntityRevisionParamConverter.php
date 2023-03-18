<?php

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity revision IDs to full objects.
 *
 * This is useful for pages which want to show a specific revision, like
 * "/entity_example/{entity_example}/revision/{entity_example_revision}".
 *
 *
 * In order to use it you should specify some additional options in your route:
 * @code
 * example.route:
 *   path: /foo/{entity_example_revision}
 *   options:
 *     parameters:
 *       entity_example_revision:
 *         type: entity_revision:entity_example
 * @endcode
 */
class EntityRevisionParamConverter implements ParamConverterInterface {

  use DynamicEntityTypeParamConverterTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Creates a new EntityRevisionParamConverter instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->loadRevision($value);

    // If the entity type is translatable, ensure we return the proper
    // translation object for the current context.
    if ($entity instanceof EntityInterface && $entity instanceof TranslatableInterface) {
      $entity = $this->entityRepository->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return isset($definition['type']) && str_contains($definition['type'], 'entity_revision:');
  }

}
