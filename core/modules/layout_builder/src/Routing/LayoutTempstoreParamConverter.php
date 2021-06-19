<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Loads the section storage from the layout tempstore.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutTempstoreParamConverter implements ParamConverterInterface {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * Constructs a new LayoutTempstoreParamConverter.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, SectionStorageManagerInterface $section_storage_manager) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->sectionStorageManager = $section_storage_manager;
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
    if ($section_storage = $this->sectionStorageManager->load($type, $contexts)) {
      // Ensure that all context values pass validation.
      if (($violations = $section_storage->validateContexts()) && !$violations->count()) {
        // Pass the plugin through the tempstore repository.
        return $this->layoutTempstoreRepository->get($section_storage);
      }
    }

    // If the section storage plugin fails validation, load the defaults section
    // storage to trigger a type mismatch in the access check.
    //
    // @see \Drupal\layout_builder\Access\LayoutBuilderAccessCheck::access()
    if ($section_storage instanceof OverridesSectionStorage && $section_storage = $section_storage->getDefaultSectionStorage()) {
      // Pass the plugin through the tempstore repository.
      return $this->layoutTempstoreRepository->get($section_storage);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['layout_builder_tempstore']);
  }

}
