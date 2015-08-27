<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\migrate\destination\EntityImageStyle.
 */

namespace Drupal\image\Plugin\migrate\destination;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Row;

/**
 * Every migration that uses this destination must have an optional
 * dependency on the d6_file migration to ensure it runs first.
 *
 * @MigrateDestination(
 *   id = "entity:image_style"
 * )
 */
class EntityImageStyle extends EntityConfigBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $effects = [];

    // Need to set the effects property to null on the row before the ImageStyle
    // is created, this prevents improper effect plugin initialization.
    if ($row->getDestinationProperty('effects')) {
      $effects = $row->getDestinationProperty('effects');
      $row->setDestinationProperty('effects', []);
    }

    /** @var \Drupal\Image\Entity\ImageStyle $style */
    $style = $this->getEntity($row, $old_destination_id_values);

    // Iterate the effects array so each effect plugin can be initialized.
    // Catch any missing plugin exceptions.
    foreach ($effects as $effect) {
      try {
        $style->addImageEffect($effect);
      }
      catch (PluginNotFoundException $e) {
        throw new MigrateException($e->getMessage(), 0, $e);
      }
    }

    $style->save();

    return array($style->id());
  }

}
