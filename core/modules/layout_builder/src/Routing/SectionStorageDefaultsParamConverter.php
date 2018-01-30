<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\ParamConverter\EntityConverter;

/**
 * Provides a param converter for defaults-based section storage.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class SectionStorageDefaultsParamConverter extends EntityConverter implements SectionStorageParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!$value) {
      // If a bundle is not provided but a value corresponding to the bundle key
      // is, use that for the bundle value.
      if (empty($defaults['bundle']) && isset($defaults['bundle_key']) && !empty($defaults[$defaults['bundle_key']])) {
        $defaults['bundle'] = $defaults[$defaults['bundle_key']];
      }

      if (empty($defaults['entity_type_id']) && empty($defaults['bundle']) && empty($defaults['view_mode_name'])) {
        return NULL;
      }

      $value = $defaults['entity_type_id'] . '.' . $defaults['bundle'] . '.' . $defaults['view_mode_name'];
    }
    if (!$display = parent::convert($value, $definition, $name, $defaults)) {
      list($entity_type_id, $bundle, $view_mode) = explode('.', $value);
      $display = $this->entityManager->getStorage('entity_view_display')->create([
        'targetEntityType' => $entity_type_id,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeFromDefaults($definition, $name, array $defaults) {
    return 'entity_view_display';
  }

}
