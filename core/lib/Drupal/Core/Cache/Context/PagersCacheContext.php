<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\Pager\PagerParametersInterface;

/**
 * Defines a cache context for "per page in a pager" caching.
 *
 * Cache context ID: 'url.query_args.pagers' (to vary by all pagers).
 * Calculated cache context ID: 'url.query_args.pagers:%pager_id', e.g.
 * 'url.query_args.pagers:1' (to vary by the pager with ID 1).
 */
class PagersCacheContext implements CalculatedCacheContextInterface {

  use DeprecatedServicePropertyTrait;

  /**
   * {@inheritdoc}
   */
  protected $deprecatedProperties = ['requestStack' => 'request_stack'];

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
  public function __construct($pager_params) {
    if (!($pager_params instanceof PagerParametersInterface)) {
      @trigger_error('Calling ' . __METHOD__ . ' with a $pager_params argument that does not implement \Drupal\Core\Pager\PagerParametersInterface is deprecated in drupal:8.8.0 and is required in drupal:9.0.0. See https://www.drupal.org/node/2779457', E_USER_DEPRECATED);
      $pager_params = \Drupal::service('pager.parameters');
    }
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
