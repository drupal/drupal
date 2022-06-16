<?php

namespace Drupal\quickedit\Entity;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\quickedit\LayoutBuilderIntegration;

/**
 * Provides an entity view display entity that has a layout with quickedit.
 */
class QuickEditLayoutBuilderEntityViewDisplay extends LayoutBuilderEntityViewDisplay {

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    if ($this->isLayoutBuilderEnabled() && $section_component = $this->getQuickEditSectionComponent()) {
      $plugin = $section_component->getPlugin();
      if ($plugin instanceof ConfigurableInterface) {
        $configuration = $plugin->getConfiguration();
        if (isset($configuration['formatter'])) {
          return $configuration['formatter'];
        }
      }
    }
    return parent::getComponent($name);
  }

  /**
   * Returns the Quick Edit formatter settings.
   *
   * @return \Drupal\layout_builder\SectionComponent|null
   *   The section component if it is available.
   *
   * @see \Drupal\quickedit\LayoutBuilderIntegration::entityViewAlter()
   * @see \Drupal\quickedit\MetadataGenerator::generateFieldMetadata()
   */
  private function getQuickEditSectionComponent() {
    // To determine the Quick Edit view_mode ID we need an originalMode set.
    if ($original_mode = $this->getOriginalMode()) {
      $parts = explode('-', $original_mode);
      // The Quick Edit view mode ID is created by
      // \Drupal\quickedit\LayoutBuilderIntegration::entityViewAlter()
      // concatenating together the information we need to retrieve the Layout
      // Builder component. It follows the structure prescribed by the
      // documentation of hook_quickedit_render_field().
      if (count($parts) === 6 && $parts[0] === 'layout_builder') {
        [, $delta, $component_uuid, $entity_id] = LayoutBuilderIntegration::deconstructViewModeId($original_mode);
        $entity = $this->entityTypeManager()->getStorage($this->getTargetEntityTypeId())->load($entity_id);
        $sections = $this->getEntitySections($entity);
        if (isset($sections[$delta])) {
          $component = $sections[$delta]->getComponent($component_uuid);
          $plugin = $component->getPlugin();
          // We only care about FieldBlock because these are only components
          // that provide Quick Edit integration: Quick Edit enables in-place
          // editing of fields of entities, not of anything else.
          if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'field_block') {
            return $component;
          }
        }
      }
    }
    return NULL;
  }

}
