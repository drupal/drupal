<?php

namespace Drupal\block_content\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieves block plugin definitions for all custom blocks.
 */
class BlockContent extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The custom block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentStorage;

  /**
   * Constructs a BlockContent object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_storage
   *   The custom block storage.
   */
  public function __construct(EntityStorageInterface $block_content_storage) {
    $this->blockContentStorage = $block_content_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('block_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $block_contents = $this->blockContentStorage->loadMultiple();
    /** @var $block_content \Drupal\block_content\Entity\BlockContent */
    foreach ($block_contents as $block_content) {
      $this->derivatives[$block_content->uuid()] = $base_plugin_definition;
      $this->derivatives[$block_content->uuid()]['admin_label'] = $block_content->label();
      $this->derivatives[$block_content->uuid()]['config_dependencies']['content'] = array(
        $block_content->getConfigDependencyName()
      );
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
