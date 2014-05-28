<?php

/**
 * @file
 * Contains \Drupal\system\Theme\BatchNegotiator.
 */

namespace Drupal\system\Theme;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

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
   * Constructs a BatchNegotiator.
   *
   * @param \Drupal\Core\Batch\BatchStorageInterface $batch_storage
   *   The batch storage.
   */
  public function __construct(BatchStorageInterface $batch_storage) {
    $this->batchStorage = $batch_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'system.batch_page';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    // Retrieve the current state of the batch.
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
