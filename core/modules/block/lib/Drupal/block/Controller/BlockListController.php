<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockListController.
 */

namespace Drupal\block\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManager;

/**
 * Defines a controller to list blocks.
 */
class BlockListController extends EntityListController {

  /**
   * The configuration factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;


  /**
   * Creates an BlockListController object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *  Configuration factory object.
   */
  public function __construct(EntityManager $entity_manager, ConfigFactory $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity'),
      $container->get('config.factory')
    );
  }

  /**
   * Shows the block administration page.
   *
   * @param string $entity_type
   *   Entity type of list page.
   * @param string|null $theme
   *   Theme key of block list.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing($entity_type, $theme = NULL) {
    $default_theme = $theme ?: $this->configFactory->get('system.theme')->get('default');
    return $this->entityManager->getListController($entity_type)->render($default_theme);
  }

}
