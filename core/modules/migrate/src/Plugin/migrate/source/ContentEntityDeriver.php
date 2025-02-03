<?php

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for content entity source plugins.
 */
class ContentEntityDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a new ContentEntityDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): ?array {
    $this->derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        $this->derivatives[$id] = $base_plugin_definition;
        // Provide entity_type so the source can be used apart from a deriver.
        $this->derivatives[$id]['entity_type'] = $id;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
