<?php

namespace Drupal\Core\Datetime;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UnchangingCacheableDependencyTrait;
use Drupal\Core\Render\RenderableInterface;

/**
 * Contains a formatted time difference.
 */
class FormattedDateDiff implements RenderableInterface, CacheableDependencyInterface {

  use UnchangingCacheableDependencyTrait;

  /**
   * The actual formatted time difference.
   *
   * @var string
   */
  protected $string;

  /**
   * The maximum time in seconds that this string may be cached.
   *
   * Let's say the time difference is 1 day 1 hour. In this case, we can cache
   * it until now + 1 hour, so maxAge is 3600 seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * Creates a new FormattedDateDiff instance.
   *
   * @param string $string
   *   The formatted time difference.
   * @param int $max_age
   *   The maximum time in seconds that this string may be cached.
   */
  public function __construct($string, $max_age) {
    $this->string = $string;
    $this->maxAge = $max_age;
  }

  /**
   * @return string
   */
  public function getString() {
    return $this->string;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->maxAge;
  }

  /**
   * The maximum age for which this object may be cached.
   *
   * @return int
   *   The maximum time in seconds that this object may be cached.
   *
   * @deprecated in Drupal 8.1.9 and will be removed before Drupal 9.0.0. Use
   *   \Drupal\Core\Datetime\FormattedDateDiff::getCacheMaxAge() instead.
   */
  public function getMaxAge() {
    return $this->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return [
      '#markup' => $this->string,
      '#cache' => [
        'max-age' => $this->maxAge,
      ],
    ];
  }

}
