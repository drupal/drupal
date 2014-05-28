<?php

/**
 * @file
 * Contains \Drupal\image\Entity\ImageStyle.
 */

namespace Drupal\image\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginBagsInterface;
use Drupal\Core\Routing\RequestHelper;
use Drupal\image\ImageEffectBag;
use Drupal\image\ImageEffectInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Defines an image style configuration entity.
 *
 * @ConfigEntityType(
 *   id = "image_style",
 *   label = @Translation("Image style"),
 *   controllers = {
 *     "form" = {
 *       "add" = "Drupal\image\Form\ImageStyleAddForm",
 *       "edit" = "Drupal\image\Form\ImageStyleEditForm",
 *       "delete" = "Drupal\image\Form\ImageStyleDeleteForm",
 *       "flush" = "Drupal\image\Form\ImageStyleFlushForm"
 *     },
 *     "list_builder" = "Drupal\image\ImageStyleListBuilder",
 *   },
 *   admin_permission = "administer image styles",
 *   config_prefix = "style",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "flush-form" = "image.style_flush",
 *     "edit-form" = "image.style_edit",
 *     "delete-form" = "image.style_delete"
 *   }
 * )
 */
class ImageStyle extends ConfigEntityBase implements ImageStyleInterface, EntityWithPluginBagsInterface {

  /**
   * The name of the image style to use as replacement upon delete.
   *
   * @var string
   */
  protected $replacementID;

  /**
   * The name of the image style.
   *
   * @var string
   */
  public $name;

  /**
   * The image style label.
   *
   * @var string
   */
  public $label;

  /**
   * The array of image effects for this image style.
   *
   * @var array
   */
  protected $effects = array();

  /**
   * Holds the collection of image effects that are used by this image style.
   *
   * @var \Drupal\image\ImageEffectBag
   */
  protected $effectsBag;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update) {
      if (!empty($this->original) && $this->id() !== $this->original->id()) {
        // The old image style name needs flushing after a rename.
        $this->original->flush();
        // Update field instance settings if necessary.
        if (!$this->isSyncing()) {
          static::replaceImageStyle($this);
        }
      }
      else {
        // Flush image style when updating without changing the name.
        $this->flush();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $style) {
      // Flush cached media for the deleted style.
      $style->flush();
      // Check whether field instance settings need to be updated.
      // In case no replacement style was specified, all image fields that are
      // using the deleted style are left in a broken state.
      if (!$style->isSyncing() && $new_id = $style->getReplacementID()) {
        // The deleted ID is still set as originalID.
        $style->setName($new_id);
        static::replaceImageStyle($style);
      }
    }
  }

  /**
   * Update field instance settings if the image style name is changed.
   *
   * @param \Drupal\image\ImageStyleInterface $style
   *   The image style.
   */
  protected static function replaceImageStyle(ImageStyleInterface $style) {
    if ($style->id() != $style->getOriginalId()) {
      // Loop through all entity displays looking for formatters / widgets using
      // the image style.
      foreach (entity_load_multiple('entity_view_display') as $display) {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['type']) && $options['type'] == 'image' && $options['settings']['image_style'] == $style->getOriginalId()) {
            $options['settings']['image_style'] = $style->id();
            $display->setComponent($name, $options)
              ->save();
          }
        }
      }
      foreach (entity_load_multiple('entity_form_display') as $display) {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['type']) && $options['type'] == 'image_image' && $options['settings']['preview_image_style'] == $style->getOriginalId()) {
            $options['settings']['preview_image_style'] = $style->id();
            $display->setComponent($name, $options)
              ->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildUri($uri) {
    $scheme = file_uri_scheme($uri);
    if ($scheme) {
      $path = file_uri_target($uri);
    }
    else {
      $path = $uri;
      $scheme = file_default_scheme();
    }
    return $scheme . '://styles/' . $this->id() . '/' . $scheme . '/' . $path;
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl($path, $clean_urls = NULL) {
    $uri = $this->buildUri($path);
    // The token query is added even if the
    // 'image.settings:allow_insecure_derivatives' configuration is TRUE, so
    // that the emitted links remain valid if it is changed back to the default
    // FALSE. However, sites which need to prevent the token query from being
    // emitted at all can additionally set the
    // 'image.settings:suppress_itok_output' configuration to TRUE to achieve
    // that (if both are set, the security token will neither be emitted in the
    // image derivative URL nor checked for in
    // \Drupal\image\ImageStyleInterface::deliver()).
    $token_query = array();
    if (!\Drupal::config('image.settings')->get('suppress_itok_output')) {
      // The passed $path variable can be either a relative path or a full URI.
      $original_uri = file_uri_scheme($path) ? file_stream_wrapper_uri_normalize($path) : file_build_uri($path);
      $token_query = array(IMAGE_DERIVATIVE_TOKEN => $this->getPathToken($original_uri));
    }

    if ($clean_urls === NULL) {
      // Assume clean URLs unless the request tells us otherwise.
      $clean_urls = TRUE;
      try {
        $request = \Drupal::request();
        $clean_urls = RequestHelper::isCleanUrl($request);
      }
      catch (ServiceNotFoundException $e) {
      }
    }

    // If not using clean URLs, the image derivative callback is only available
    // with the script path. If the file does not exist, use url() to ensure
    // that it is included. Once the file exists it's fine to fall back to the
    // actual file path, this avoids bootstrapping PHP once the files are built.
    if ($clean_urls === FALSE && file_uri_scheme($uri) == 'public' && !file_exists($uri)) {
      $directory_path = file_stream_wrapper_get_instance_by_uri($uri)->getDirectoryPath();
      return url($directory_path . '/' . file_uri_target($uri), array('absolute' => TRUE, 'query' => $token_query));
    }

    $file_url = file_create_url($uri);
    // Append the query string with the token, if necessary.
    if ($token_query) {
      $file_url .= (strpos($file_url, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($token_query);
    }

    return $file_url;
  }

  /**
   * {@inheritdoc}
   */
  public function flush($path = NULL) {
    // A specific image path has been provided. Flush only that derivative.
    if (isset($path)) {
      $derivative_uri = $this->buildUri($path);
      if (file_exists($derivative_uri)) {
        file_unmanaged_delete($derivative_uri);
      }
      return $this;
    }

    // Delete the style directory in each registered wrapper.
    $wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE);
    foreach ($wrappers as $wrapper => $wrapper_data) {
      if (file_exists($directory = $wrapper . '://styles/' . $this->id())) {
        file_unmanaged_delete_recursive($directory);
      }
    }

    // Let other modules update as necessary on flush.
    $module_handler = \Drupal::moduleHandler();
    $module_handler->invokeAll('image_style_flush', array($this));

    // Clear caches so that formatters may be added for this style.
    drupal_theme_rebuild();

    Cache::invalidateTags($this->getCacheTag());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createDerivative($original_uri, $derivative_uri) {
    // Get the folder for the final location of this style.
    $directory = drupal_dirname($derivative_uri);

    // Build the destination folder tree if it doesn't already exist.
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      watchdog('image', 'Failed to create style directory: %directory', array('%directory' => $directory), WATCHDOG_ERROR);
      return FALSE;
    }

    $image = \Drupal::service('image.factory')->get($original_uri);
    if (!$image->isExisting()) {
      return FALSE;
    }

    foreach ($this->getEffects() as $effect) {
      $effect->applyEffect($image);
    }

    if (!$image->save($derivative_uri)) {
      if (file_exists($derivative_uri)) {
        watchdog('image', 'Cached image file %destination already exists. There may be an issue with your rewrite configuration.', array('%destination' => $derivative_uri), WATCHDOG_ERROR);
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    foreach ($this->getEffects() as $effect) {
      $effect->transformDimensions($dimensions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPathToken($uri) {
    // Return the first 8 characters.
    return substr(Crypt::hmacBase64($this->id() . ':' . $uri, \Drupal::service('private_key')->get() . drupal_get_hash_salt()), 0, 8);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteImageEffect(ImageEffectInterface $effect) {
    $this->getEffects()->removeInstanceId($effect->getUuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEffect($effect) {
    return $this->getEffects()->get($effect);
  }

  /**
   * {@inheritdoc}
   */
  public function getEffects() {
    if (!$this->effectsBag) {
      $this->effectsBag = new ImageEffectBag(\Drupal::service('plugin.manager.image.effect'), $this->effects);
      $this->effectsBag->sort();
    }
    return $this->effectsBag;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginBags() {
    return array('effects' => $this->getEffects());
  }

  /**
   * {@inheritdoc}
   */
  public function saveImageEffect(array $configuration) {
    $effect_id = $this->getEffects()->updateConfiguration($configuration);
    $this->save();
    return $effect_id;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = parent::toArray();
    $names = array(
      'effects',
    );
    foreach ($names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementID() {
    return $this->get('replacementID');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

}
