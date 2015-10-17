<?php

/**
 * @file
 * Contains \Drupal\entity_test\Controller\EntityTestController.
 */

namespace Drupal\entity_test\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for entity_test routes.
 */
class EntityTestController extends ControllerBase {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Constructs a new EntityTestController.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $entity_query_factory) {
    $this->entityQueryFactory = $entity_query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Displays the 'Add new entity_test' form.
   *
   * @param string $entity_type_id
   *   Name of the entity type for which a create form should be displayed.
   *
   * @return array
   *   The processed form for a new entity_test.
   *
   * @see \Drupal\entity_test\Routing\EntityTestRoutes::routes()
   */
  public function testAdd($entity_type_id) {
    $entity = entity_create($entity_type_id, array());
    $form = $this->entityFormBuilder()->getForm($entity);
    $form['#title'] = $this->t('Create an @type', array('@type' => $entity_type_id));
    return $form;
  }

  /**
   * Returns an empty page.
   *
   * @see \Drupal\entity_test\Routing\EntityTestRoutes::routes()
   */
  public function testAdmin() {
    return [];
  }

  /**
   * List entity_test entities referencing the given entity.
   *
   * @param string $entity_reference_field_name
   *   The name of the entity_reference field to use in the query.
   * @param string $referenced_entity_type
   *   The type of the entity being referenced.
   * @param int $referenced_entity_id
   *   The ID of the entity being referenced.
   *
   * @return array
   *   A renderable array.
   */
  public function listReferencingEntities($entity_reference_field_name, $referenced_entity_type, $referenced_entity_id) {
    // Early return if the referenced entity does not exist (or is deleted).
    $referenced_entity = $this->entityManager()
      ->getStorage($referenced_entity_type)
      ->load($referenced_entity_id);
    if ($referenced_entity === NULL) {
      return array();
    }

    $query = $this->entityQueryFactory
      ->get('entity_test')
      ->condition($entity_reference_field_name . '.target_id', $referenced_entity_id);
    $entities = $this->entityManager()
      ->getStorage('entity_test')
      ->loadMultiple($query->execute());
    return $this->entityManager()
      ->getViewBuilder('entity_test')
      ->viewMultiple($entities, 'full');
  }

  /**
   * List entities of the given entity type labels, sorted alphabetically.
   *
   * @param string $entity_type_id
   *   The type of the entity being listed.
   *
   * @return array
   *   A renderable array.
   */
  public function listEntitiesAlphabetically($entity_type_id) {
    $entity_type_definition = $this->entityManager()->getDefinition($entity_type_id);
    $query = $this->entityQueryFactory->get($entity_type_id);

    // Sort by label field, if any.
    if ($label_field = $entity_type_definition->getKey('label')) {
      $query->sort($label_field);
    }

    $entities = $this->entityManager()
      ->getStorage($entity_type_id)
      ->loadMultiple($query->execute());

    $cache_tags = [];
    $labels = [];
    foreach ($entities as $entity) {
      $labels[] = $entity->label();
      $cache_tags = Cache::mergeTags($cache_tags, $entity->getCacheTags());
    }
    // Always associate the list cache tag, otherwise the cached empty result
    // wouldn't be invalidated. This would continue to show nothing matches the
    // query, even though a newly created entity might match the query.
    $cache_tags = Cache::mergeTags($cache_tags, $entity_type_definition->getListCacheTags());

    return [
      '#theme' => 'item_list',
      '#items' => $labels,
      '#title' => $entity_type_id . ' entities',
      '#cache' => [
        'contexts' => $entity_type_definition->getListCacheContexts(),
        'tags' => $cache_tags,
      ],
    ];
  }


  /**
   * Empty list of entities of the given entity type.
   *
   * Empty because no entities match the query. That may seem contrived, but it
   * is an excellent way for testing whether an entity's list cache tags are
   * working as expected.
   *
   * @param string $entity_type_id
   *   The type of the entity being listed.
   *
   * @return array
   *   A renderable array.
   */
  public function listEntitiesEmpty($entity_type_id) {
    $entity_type_definition = $this->entityManager()->getDefinition($entity_type_id);
    return [
      '#theme' => 'item_list',
      '#items' => [],
      '#cache' => [
        'contexts' => $entity_type_definition->getListCacheContexts(),
        'tags' => $entity_type_definition->getListCacheTags(),
      ],
    ];
  }

}
