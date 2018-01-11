<?php

namespace Drupal\layout_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for the layout builder user interface.
 *
 * @internal
 */
class LayoutBuilderLocalTaskDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LayoutBuilderLocalTaskDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach (array_keys($this->getEntityTypes()) as $entity_type_id) {
      $this->derivatives["entity.$entity_type_id.layout_builder"] = $base_plugin_definition + [
        'route_name' => "entity.$entity_type_id.layout_builder",
        'weight' => 15,
        'title' => $this->t('Layout'),
        'base_route' => "entity.$entity_type_id.canonical",
        'entity_type_id' => $entity_type_id,
        'cache_contexts' => ['layout_builder_is_active:' . $entity_type_id],
      ];
      $this->derivatives["entity.$entity_type_id.layout_builder_save"] = $base_plugin_definition + [
        'route_name' => "entity.$entity_type_id.layout_builder_save",
        'title' => $this->t('Save Layout'),
        'parent_id' => "layout_builder_ui:entity.$entity_type_id.layout_builder",
        'entity_type_id' => $entity_type_id,
        'cache_contexts' => ['layout_builder_is_active:' . $entity_type_id],
      ];
      $this->derivatives["entity.$entity_type_id.layout_builder_cancel"] = $base_plugin_definition + [
        'route_name' => "entity.$entity_type_id.layout_builder_cancel",
        'title' => $this->t('Cancel Layout'),
        'parent_id' => "layout_builder_ui:entity.$entity_type_id.layout_builder",
        'entity_type_id' => $entity_type_id,
        'weight' => 5,
        'cache_contexts' => ['layout_builder_is_active:' . $entity_type_id],
      ];
    }

    return $this->derivatives;
  }

  /**
   * Returns an array of relevant entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    return array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type->hasLinkTemplate('layout-builder');
    });
  }

}
