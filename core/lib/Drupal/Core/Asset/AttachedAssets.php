<?php
/**
 * @file
 * Contains \Drupal\Core\Asset\AttachedAssets.
 */

namespace Drupal\Core\Asset;

/**
 * The default attached assets collection.
 */
class AttachedAssets implements AttachedAssetsInterface {

  /**
   * The (ordered) list of asset libraries attached to the current response.
   *
   * @var string[]
   */
  public $libraries = [];

  /**
   * The JavaScript settings attached to the current response.
   *
   * @var array
   */
  public $settings = [];

  /**
   * The set of asset libraries that the client has already loaded.
   *
   * @var string[]
   */
  protected $alreadyLoadedLibraries = [];

  /**
   * {@inheritdoc}
   */
  public static function createFromRenderArray(array $render_array) {
    if (!isset($render_array['#attached'])) {
      throw new \LogicException('The render array has not yet been rendered, hence not all attachments have been collected yet.');
    }

    $assets = new static();
    if (isset($render_array['#attached']['library'])) {
      $assets->setLibraries($render_array['#attached']['library']);
    }
    if (isset($render_array['#attached']['drupalSettings'])) {
      $assets->setSettings($render_array['#attached']['drupalSettings']);
    }
    return $assets;
  }

  /**
   * {@inheritdoc}
   */
  public function setLibraries(array $libraries) {
    $this->libraries = array_unique($libraries);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlreadyLoadedLibraries() {
    return $this->alreadyLoadedLibraries;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlreadyLoadedLibraries(array $libraries) {
    $this->alreadyLoadedLibraries = $libraries;
    return $this;
  }

}
