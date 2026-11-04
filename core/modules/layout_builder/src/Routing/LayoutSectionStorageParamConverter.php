<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Loads the section storage from the routing defaults.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutSectionStorageParamConverter implements ParamConverterInterface {

  /**
   * Constructs a new LayoutSectionStorageParamConverter.
   *
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $sectionStorageManager
   *   The section storage manager.
   */
  public function __construct(
    protected SectionStorageManagerInterface $sectionStorageManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // If no section storage type is specified or if it is invalid, return.
    if (!isset($defaults['section_storage_type']) || !$this->sectionStorageManager->hasDefinition($defaults['section_storage_type'])) {
      return NULL;
    }

    $type = $defaults['section_storage_type'];
    // Load an empty instance and derive the available contexts.
    $contexts = $this->sectionStorageManager->loadEmpty($type)->deriveContextsFromRoute($value, $definition, $name, $defaults);
    // Attempt to load a full instance based on the context.
    return $this->sectionStorageManager->load($type, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['layout_builder_section_storage']) || !empty($definition['layout_builder_tempstore']);
  }

}
