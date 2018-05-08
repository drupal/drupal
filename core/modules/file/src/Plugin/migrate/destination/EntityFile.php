<?php

namespace Drupal\file\Plugin\migrate\destination;

use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * @MigrateDestination(
 *   id = "entity:file"
 * )
 */
class EntityFile extends EntityContentBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    // For stub rows, there is no real file to deal with, let the stubbing
    // process take its default path.
    if ($row->isStub()) {
      return parent::getEntity($row, $old_destination_id_values);
    }

    // By default the entity key (fid) would be used, but we want to make sure
    // we're loading the matching URI.
    $destination = $row->getDestinationProperty('uri');
    if (empty($destination)) {
      throw new MigrateException('Destination property uri not provided');
    }
    $entity = $this->storage->loadByProperties(['uri' => $destination]);
    if ($entity) {
      return reset($entity);
    }
    else {
      return parent::getEntity($row, $old_destination_id_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    // We stub the uri value ourselves so we can create a real stub file for it.
    if (!$row->getDestinationProperty('uri')) {
      $field_definitions = $this->entityManager
        ->getFieldDefinitions($this->storage->getEntityTypeId(),
          $this->getKey('bundle'));
      $value = UriItem::generateSampleValue($field_definitions['uri']);
      if (empty($value)) {
        throw new MigrateException('Stubbing failed, unable to generate value for field uri');
      }
      // generateSampleValue() wraps the value in an array.
      $value = reset($value);
      // Make it into a proper public file uri, stripping off the existing
      // scheme if present.
      $value = 'public://' . preg_replace('|^[a-z]+://|i', '', $value);
      $value = mb_substr($value, 0, $field_definitions['uri']->getSetting('max_length'));
      // Create a real file, so File::preSave() can do filesize() on it.
      touch($value);
      $row->setDestinationProperty('uri', $value);
    }
    parent::processStubRow($row);
  }

}
