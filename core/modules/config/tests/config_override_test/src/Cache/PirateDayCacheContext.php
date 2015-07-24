<?php

/**
 * @file
 * Contains \Drupal\config_override_test\Cache\PirateDayCacheContext.
 */

namespace Drupal\config_override_test\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the PirateDayCacheContext service that allows to cache the booty.
 *
 * Cache context ID: 'pirate_day'.
 */
class PirateDayCacheContext implements CacheContextInterface {

  /**
   * The length of Pirate Day. It lasts 24 hours.
   *
   * This is a simplified test implementation. In a real life Pirate Day module
   * this data wouldn't be defined in a constant, but calculated in a static
   * method. If it were Pirate Day it should return the number of seconds until
   * midnight, and on all other days it should return the number of seconds
   * until the start of the next Pirate Day.
   */
  const PIRATE_DAY_MAX_AGE = 86400;

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Pirate day');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $is_pirate_day = static::isPirateDay() ? 'yarr' : 'nay';
    return "pirate_day." . $is_pirate_day;
  }

  /**
   * Returns whether or not it is Pirate Day.
   *
   * To ease testing this is determined with a global variable rather than using
   * the traditional compass and sextant.
   *
   * @return bool
   *   Returns TRUE if it is Pirate Day today.
   */
  public static function isPirateDay() {
    return !empty($GLOBALS['it_is_pirate_day']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
