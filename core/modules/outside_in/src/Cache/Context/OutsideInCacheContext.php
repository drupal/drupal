<?php

namespace Drupal\outside_in\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\outside_in\OutsideInManagerInterface;

/**
 * Defines the OutsideInCacheContext service, for "Outside-In or not" caching.
 *
 * Cache context ID: 'outside_in_is_applied'.
 */
class OutsideInCacheContext implements CacheContextInterface {

  /**
   * The Outside-In manager.
   *
   * @var \Drupal\outside_in\OutsideInManagerInterface
   */
  protected $outsideInManager;

  /**
   * OutsideInCacheContext constructor.
   *
   * @param \Drupal\outside_in\OutsideInManagerInterface $outside_in_manager
   *   The Outside-In manager.
   */
  public function __construct(OutsideInManagerInterface $outside_in_manager) {
    $this->outsideInManager = $outside_in_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Settings Tray');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->outsideInManager->isApplicable() ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
