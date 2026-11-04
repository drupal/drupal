<?php

namespace Drupal\system\Theme;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets the active theme for the batch page.
 */
class BatchNegotiator implements ThemeNegotiatorInterface {

  /**
   * Constructs a BatchNegotiator.
   *
   * @param \Drupal\Core\Batch\BatchStorageInterface $batchStorage
   *   The batch storage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(
    protected BatchStorageInterface $batchStorage,
    protected RequestStack $requestStack,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'system.batch_page.html';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Retrieve the current state of the batch.
    $request = $this->requestStack->getCurrentRequest();
    $batch = &batch_get();
    if (!$batch && $request->request->has('id')) {
      $batch = $this->batchStorage->load($request->request->get('id'));
    }
    // Use the same theme as the page that started the batch.
    if (!empty($batch['theme'])) {
      return $batch['theme'];
    }
  }

}
