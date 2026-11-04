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
   * Creates a new FormattedDateDiff instance.
   *
   * @param string $string
   *   The formatted time difference.
   * @param int $maxAge
   *   The maximum time in seconds that this string may be cached.
   */
  public function __construct(
    protected $string,
    protected $maxAge,
  ) {
  }

  /**
   * @return string
   *   The actual formatted time difference.
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
