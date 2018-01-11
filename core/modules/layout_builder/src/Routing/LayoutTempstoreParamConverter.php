<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
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
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new LayoutTempstoreParamConverter.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ClassResolverInterface $class_resolver) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($converter = $this->getParamConverterFromDefaults($defaults)) {
      if ($object = $converter->convert($value, $definition, $name, $defaults)) {
        // Pass the result of the storage param converter through the
        // tempstore repository.
        return $this->layoutTempstoreRepository->get($object);
      }
    }
  }

  /**
   * Gets a param converter based on the provided defaults.
   *
   * @param array $defaults
   *   The route defaults array.
   *
   * @return \Drupal\layout_builder\Routing\SectionStorageParamConverterInterface|null
   *   A section storage param converter if found, NULL otherwise.
   */
  protected function getParamConverterFromDefaults(array $defaults) {
    // If a storage type was specified, get the corresponding param converter.
    if (isset($defaults['section_storage_type'])) {
      try {
        $converter = $this->classResolver->getInstanceFromDefinition('layout_builder.section_storage_param_converter.' . $defaults['section_storage_type']);
      }
      catch (\InvalidArgumentException $e) {
        $converter = NULL;
      }

      if ($converter instanceof SectionStorageParamConverterInterface) {
        return $converter;
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
