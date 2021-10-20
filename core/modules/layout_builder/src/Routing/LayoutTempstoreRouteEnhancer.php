<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loads the section storage from the layout tempstore.
 */
class LayoutTempstoreRouteEnhancer implements EnhancerInterface {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Constructs a new LayoutTempstoreRouteEnhancer.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $parameters = $defaults[RouteObjectInterface::ROUTE_OBJECT]->getOption('parameters');
    if (isset($parameters['section_storage']['layout_builder_tempstore']) && isset($defaults['section_storage']) && $defaults['section_storage'] instanceof SectionStorageInterface) {
      $defaults['section_storage'] = $this->layoutTempstoreRepository->get($defaults['section_storage']);
    }
    return $defaults;
  }

}
