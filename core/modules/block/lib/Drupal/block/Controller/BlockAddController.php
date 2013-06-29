<?php

/**
 * @file
 * Contains \Drupal\block\Controller\BlockAddController.
 */

namespace Drupal\block\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the block instance add form.
 */
class BlockAddController implements ControllerInterface {

  /**
   * Constructs a Block object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity manager service.
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
   * Build the block instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the block instance.
   * @param string $theme
   *   The name of the theme for the block instance.
   *
   * @return array
   *   The block instance edit form.
   */
  public function blockAddConfigureForm($plugin_id, $theme) {
    // Set the page title.
    drupal_set_title(t('Configure block'));

    // Create a block entity.
    $entity = $this->entityManager->getStorageController('block')->create(array('plugin' => $plugin_id, 'theme' => $theme));

    return $this->entityManager->getForm($entity);
  }
}
