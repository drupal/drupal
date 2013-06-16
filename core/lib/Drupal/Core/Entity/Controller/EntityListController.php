<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Controller\EntityListController.
 */

namespace Drupal\Core\Entity\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic controller to list entities.
 */
class EntityListController implements ControllerInterface {

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Creates an EntityListController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')
    );
  }

  /**
   * Provides the listing page for any entity type.
   *
   * @param string $entity_type
   *   The entity type to render.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entity_type) {
    return $this->entityManager->getListController($entity_type)->render();
  }

}

