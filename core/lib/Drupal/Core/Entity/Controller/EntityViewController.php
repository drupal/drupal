<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Controller\EntityViewController.
 */

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic controller to render a single entity.
 */
class EntityViewController implements ContainerInjectionInterface {

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an EntityViewController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Provides a page to render a single entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   The Entity to be rendered. Note this variable is named $_entity rather
   *   than $entity to prevent collisions with other named placeholders in the
   *   route.
   * @param string $view_mode
   *   (optional) The view mode that should be used to display the entity.
   *   Defaults to 'full'.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function view(EntityInterface $_entity, $view_mode = 'full', $langcode = NULL) {
    $page = $this->entityManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode, $langcode);

    // If the entity's label is rendered using a field formatter, set the
    // rendered title field formatter as the page title instead of the default
    // plain text title. This allows attributes set on the field to propagate
    // correctly (e.g. RDFa, in-place editing).
    if ($_entity instanceof FieldableEntityInterface) {
      $label_field = $_entity->getEntityType()->getKey('label');
      if ($label_field && $_entity->getFieldDefinition($label_field)->getDisplayOptions('view')) {
        // We must render the label field, because rendering the entity may be
        // a cache hit, in which case we can't extract the rendered label field
        // from the $page renderable array.
        $build = $this->entityManager->getTranslationFromContext($_entity)
          ->get($label_field)
          ->view($view_mode);
        $page['#title'] = drupal_render($build, TRUE);
      }
    }

    return $page;
  }

}
