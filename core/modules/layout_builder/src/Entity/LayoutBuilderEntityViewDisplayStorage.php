<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides storage for entity view display entities that have layouts.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
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
    foreach ($records as $id => &$record) {
      if (!empty($record['third_party_settings']['layout_builder']['sections'])) {
        $sections = &$record['third_party_settings']['layout_builder']['sections'];
        foreach ($sections as $section_delta => $section) {
          $sections[$section_delta] = new Section(
            $section['layout_id'],
            $section['layout_settings'],
            array_map(function (array $component) {
              return (new SectionComponent(
                $component['uuid'],
                $component['region'],
                $component['configuration'],
                $component['additional']
              ))->setWeight($component['weight']);
            }, $section['components'])
          );
        }
      }
    }
    return parent::mapFromStorageRecords($records);
  }

}
