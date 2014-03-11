<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Derivative\SystemMenuBlock.
 */

namespace Drupal\system\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for custom menus.
 *
 * @see \Drupal\system\Plugin\Block\SystemMenuBlock
 */
class SystemMenuBlock extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The menu storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $menuStorage;

  /**
   * Constructs new SystemMenuBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $menu_storage
   *   The menu storage.
   */
  public function __construct(EntityStorageControllerInterface $menu_storage) {
    $this->menuStorage = $menu_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')->getStorageController('menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      $this->derivatives[$menu] = $base_plugin_definition;
      $this->derivatives[$menu]['admin_label'] = $entity->label();
      $this->derivatives[$menu]['cache'] = DRUPAL_NO_CACHE;
    }
    return $this->derivatives;
  }

}
