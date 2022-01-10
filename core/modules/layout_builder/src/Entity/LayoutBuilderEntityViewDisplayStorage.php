<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\Section;

/**
 * Provides storage for entity view display entities that have layouts.
 *
 * @internal
 *   Entity handlers are internal.
 */
class LayoutBuilderEntityViewDisplayStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    $record = parent::mapToStorageRecord($entity);

    if (!empty($record['third_party_settings']['layout_builder']['sections'])) {
      $record['third_party_settings']['layout_builder']['sections'] = array_map(function (Section $section) {
        return $section->toArray();
      }, $record['third_party_settings']['layout_builder']['sections']);
    }
    return $record;
  }

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records) {
    foreach ($records as &$record) {
      if (!empty($record['third_party_settings']['layout_builder']['sections'])) {
        $sections = &$record['third_party_settings']['layout_builder']['sections'];
        $sections = array_map([Section::class, 'fromArray'], $sections);
      }
    }
    return parent::mapFromStorageRecords($records);
  }

}
