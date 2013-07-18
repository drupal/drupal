<?php

/**
 * @file
 * Contains \Drupal\forum\Controller\ForumController.
 */

namespace Drupal\forum\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\taxonomy\TermStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for forum routes.
 */
class ForumController implements ControllerInterface {

  /**
   * Entity Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Config object for forum.settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageControllerInterface
   */
  protected $storageController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('config.factory'),
      $container->get('plugin.manager.entity')->getStorageController('taxonomy_term')
    );
  }

  /**
   * Constructs a ForumController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\taxonomy\TermStorageControllerInterface $storage_controller
   *   The term storage controller.
   */
  public function __construct(EntityManager $entity_manager, ConfigFactory $config_factory, TermStorageControllerInterface $storage_controller) {
    $this->entityManager = $entity_manager;
    $this->config = $config_factory->get('forum.settings');
    $this->storageController = $storage_controller;
  }

  /**
   * Returns add forum entity form.
   *
   * @return array
   *   Render array for the add form.
   */
  public function addForum() {
    $vid = $this->config->get('vocabulary');
    $taxonomy_term = $this->storageController->create(array(
      'vid' => $vid,
    ));
    return $this->entityManager->getForm($taxonomy_term, 'forum');
  }

  /**
   * Returns add container entity form.
   *
   * @return array
   *   Render array for the add form.
   */
  public function addContainer() {
    $vid = $this->config->get('vocabulary');
    $taxonomy_term = $this->storageController->create(array(
      'vid' => $vid,
    ));
    return $this->entityManager->getForm($taxonomy_term, 'container');
  }

}
