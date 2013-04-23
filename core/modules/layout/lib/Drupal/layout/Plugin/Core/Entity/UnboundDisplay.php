<?php

/**
 * @file
 * Definition of Drupal\layout\Plugin\Core\Entity\Display.
 */

namespace Drupal\layout\Plugin\Core\Entity;

use Drupal\layout\Config\DisplayBase;
use Drupal\layout\Config\BoundDisplayInterface;
use Drupal\layout\Config\UnboundDisplayInterface;
use Drupal\layout\Plugin\LayoutInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the unbound_display entity.
 *
 * Unbound displays contain blocks that are not 'bound' to a specific layout,
 * and their contained blocks are mapped only to region types, not regions.
 *
 * @EntityType(
 *   id = "unbound_display",
 *   label = @Translation("Unbound Display"),
 *   module = "layout",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController"
 *   },
 *   config_prefix = "display.unbound",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class UnboundDisplay extends DisplayBase implements UnboundDisplayInterface {

  /**
   * Implements UnboundDisplayInterface::generateDisplay().
   *
   * @throws \Exception
   */
  public function generateDisplay(LayoutInterface $layout, $id, $entity_type = 'display') {
    $values = array(
      'layout' => $layout->getPluginId(),
      'blockInfo' => $this->mapBlocksToLayout($layout),
      'id' => $id,
    );

    $entity = entity_create($entity_type, $values);

    if (!$entity instanceof BoundDisplayInterface) {
      throw new \Exception(sprintf('Attempted to bind an unbound display but provided an invalid entity type.'), E_RECOVERABLE_ERROR);
    }

    return $entity;
  }
}
