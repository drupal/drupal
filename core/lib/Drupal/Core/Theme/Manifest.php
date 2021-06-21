<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\Core\Cache\Cache;

/**
 * A value object containing all data for the manifest.
 */
class Manifest implements CacheableDependencyInterface {

  use CacheableResponseTrait;

  /**
   * An array containing all data to generate the manifest file.
   *
   * @var array
   */
  protected $data;

  /**
   * Manifest constructor.
   *
   * @param array $data
   *   An array containing all data to generate the manifest file.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['languages', 'theme'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [
      'config:system.site',
    ];
  }

  /**
   * Generate a new instance of the manifest with updated data.
   *
   * @param array $data
   *   An array containing all data to generate the manifest file.
   *
   * @return static
   */
  public function overwriteWithNewData($data) : Manifest {
    return new static($data);
  }

  /**
   * Returns the data as an array.
   *
   * @return array
   *   An array containing all data to generate the manifest file.
   */
  public function toArray() : array {
    return $this->data;
  }

}
