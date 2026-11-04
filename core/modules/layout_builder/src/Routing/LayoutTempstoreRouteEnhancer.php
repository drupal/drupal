<?php

namespace Drupal\layout_builder\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Loads the section storage from the layout tempstore.
 */
class LayoutTempstoreRouteEnhancer implements EnhancerInterface {

  /**
   * Constructs a new LayoutTempstoreRouteEnhancer.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layoutTempstoreRepository
   *   The layout tempstore repository.
   */
  public function __construct(
    protected LayoutTempstoreRepositoryInterface $layoutTempstoreRepository,
  ) {
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
