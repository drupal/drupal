<?php

namespace Drupal\field_ui\Controller;

use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a controller to list field instances.
 */
class FieldConfigListController extends EntityListController {

  /**
   * Shows the 'Manage fields' page.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    return $this->entityTypeManager()->getListBuilder('field_config')->render($entity_type_id, $bundle);
  }

  /**
   * Provides the title for the 'Manage fields' page.
   *
   * @return string
   *   The title.
   */
  public function title($entity_type_id = NULL, $bundle = NULL) {
    $target_entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
    if ($bundle_entity_type_id = $target_entity_type->getBundleEntityType()) {
      $bundle_entity = $this->entityTypeManager()->getStorage($bundle_entity_type_id)->load($bundle);

      return $this->t('Manage fields: @bundle-label', [
        '@bundle-label' => $bundle_entity->label(),
      ]);
    }
    else {
      return $this->t('Manage fields: @entity-type-label', [
        '@entity-type-label' => $target_entity_type->getLabel(),
      ]);
    }
  }

}
