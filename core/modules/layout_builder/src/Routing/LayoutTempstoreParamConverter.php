<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Symfony\Component\Routing\Route;

/**
 * Loads the entity from the layout tempstore.
 *
 * @internal
 */
class LayoutTempstoreParamConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Constructs a new LayoutTempstoreParamConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(EntityManagerInterface $entity_manager, LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    parent::__construct($entity_manager);
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if ($entity = parent::convert($value, $definition, $name, $defaults)) {
      return $this->layoutTempstoreRepository->get($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['layout_builder_tempstore']);
  }

}
