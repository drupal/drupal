<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block that renders an entity with parallel placeholder rendering.
 */
#[Block(
  id: 'entity_test_block',
  admin_label: new TranslatableMarkup('Entity test block'),
)]
class EntityTestBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'entity_type_id' => 'entity_test',
      'entity_id' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function createPlaceholder(): bool {
    // Render as a placeholder so this block is rendered in a Fiber, enabling
    // tests to verify concurrent entity rendering behavior.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $entity_type_id = $this->configuration['entity_type_id'];
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($this->configuration['entity_id']);
    if ($entity) {
      return $this->entityTypeManager->getViewBuilder($entity_type_id)->view($entity);
    }
    return [];
  }

}
