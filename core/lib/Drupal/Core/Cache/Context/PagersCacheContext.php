<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Pager\PagerParametersInterface;

/**
 * Defines a cache context for "per page in a pager" caching.
 *
 * Cache context ID: 'url.query_args.pagers' (to vary by all pagers).
 * Calculated cache context ID: 'url.query_args.pagers:%pager_id', e.g.
 * 'url.query_args.pagers:1' (to vary by the pager with ID 1).
 */
class PagersCacheContext implements CalculatedCacheContextInterface {

  /**
   * The pager parameters.
   *
   * @var \Drupal\Core\Pager\PagerParametersInterface
   */
  protected $pagerParams;

  /**
   * Constructs a new PagersCacheContext object.
   *
   * @param \Drupal\Core\Pager\PagerParametersInterface $pager_params
   *   The pager parameters.
   */
  public function __construct(PagerParametersInterface $pager_params) {
    $this->pagerParams = $pager_params;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Pager');
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Pager\PagerParametersInterface::findPage()
   */
  public function getContext($pager_id = NULL) {
    // The value of the 'page' query argument contains the information that
    // controls *all* pagers.
    if ($pager_id === NULL) {
      return $this->pagerParams->getPagerParameter();
    }

    return $pager_id . '.' . $this->pagerParams->findPage($pager_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($pager_id = NULL) {
    return new CacheableMetadata();
  }

}
