<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Loads the section storage from the layout tempstore.
 *
 * @internal
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
    if (isset($defaults['section_storage_type']) && $this->sectionStorageManager->hasDefinition($defaults['section_storage_type'])) {
      if ($section_storage = $this->sectionStorageManager->loadFromRoute($defaults['section_storage_type'], $value, $definition, $name, $defaults)) {
        // Pass the plugin through the tempstore repository.
        return $this->layoutTempstoreRepository->get($section_storage);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['layout_builder_tempstore']);
  }

}
