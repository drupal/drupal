<?php
/**
 * @file
 * Contains \Drupal\shortcut\Controller\ShortCutController.
 */

namespace Drupal\shortcut\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the page for administering shortcut sets.
 */
class ShortcutController implements ControllerInterface {

  /**
   * Stores the entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new \Drupal\shortcut\Controller\ShortCutController object.
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
    return new static($container->get('plugin.manager.entity'));
  }

  /**
   * Presents a list of layouts.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function shortcutSetAdmin() {
    return $this->entityManager->getListController('shortcut')->render();
  }

}
