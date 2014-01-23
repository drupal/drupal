<?php

/**
 * @file
 * Contains \Drupal\entity\Controller\EntityDisplayModeController.
 */

namespace Drupal\entity\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides methods for entity display mode routes.
 */
class EntityDisplayModeController implements ContainerInjectionInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityDisplayModeFormBase.
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
   * Provides a list of eligible entity types for adding view modes.
   *
   * @return array
   *   A list of entity types to add a view mode for.
   */
  public function viewModeTypeSelection() {
    $entity_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info->isFieldable() && $entity_info->hasViewBuilderClass()) {
        $entity_types[$entity_type] = array(
          'title' => $entity_info->getLabel(),
          'link_path' => 'admin/structure/display-modes/view/add/' . $entity_type,
          'localized_options' => array(),
        );
      }
    }
    return array(
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    );
  }

  /**
   * Provides a list of eligible entity types for adding form modes.
   *
   * @return array
   *   A list of entity types to add a form mode for.
   */
  public function formModeTypeSelection() {
    $entity_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
      if ($entity_info->isFieldable() && $entity_info->hasFormClasses()) {
        $entity_types[$entity_type] = array(
          'title' => $entity_info->getLabel(),
          'link_path' => 'admin/structure/display-modes/form/add/' . $entity_type,
          'localized_options' => array(),
        );
      }
    }
    return array(
      '#theme' => 'admin_block_content',
      '#content' => $entity_types,
    );
  }

}
