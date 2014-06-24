<?php

/**
 * @file
 * Contains \Drupal\system\Theme\BatchNegotiator.
 */

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
   * The batch storage.
   *
   * @var \Drupal\Core\Batch\BatchStorageInterface
   */
  protected $batchStorage;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a BatchNegotiator.
   *
   * @param \Drupal\Core\Batch\BatchStorageInterface $batch_storage
   *   The batch storage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(BatchStorageInterface $batch_storage, RequestStack $request_stack) {
    $this->batchStorage = $batch_storage;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'system.batch_page';
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
