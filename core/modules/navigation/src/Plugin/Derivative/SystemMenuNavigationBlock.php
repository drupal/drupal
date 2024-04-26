<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides navigation block plugin definitions for custom menus.
 *
 * @internal
 * @see \Drupal\navigation\Plugin\Block\NavigationMenuBlock
 */
final class SystemMenuNavigationBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs new SystemMenuNavigationBlock.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $menuStorage
   *   The menu storage.
   */
  public function __construct(protected EntityStorageInterface $menuStorage) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get('entity_type.manager')->getStorage('menu')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    foreach ($this->menuStorage->loadMultiple() as $menu => $entity) {
      $this->derivatives[$menu] = $base_plugin_definition;
      $this->derivatives[$menu]['admin_label'] = $entity->label();
      $this->derivatives[$menu]['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
    }
    return $this->derivatives;
  }

}
