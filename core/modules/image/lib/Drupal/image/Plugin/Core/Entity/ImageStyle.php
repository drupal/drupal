<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\Core\Entity\ImageStyle.
 */

namespace Drupal\image\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Url;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Defines an image style configuration entity.
 *
 * @EntityType(
 *   id = "image_style",
 *   label = @Translation("Image style"),
 *   module = "image",
 *   controllers = {
 *     "form" = {
 *       "delete" = "Drupal\image\Form\ImageStyleDeleteForm"
 *     },
 *     "storage" = "Drupal\image\ImageStyleStorageController"
 *   },
 *   uri_callback = "image_style_entity_uri",
 *   config_prefix = "image.style",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ImageStyle extends ConfigEntityBase implements ImageStyleInterface {

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
   * The UUID for this entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * The array of image effects for this image style.
   *
   * @var array
   */
  public $effects;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    if ($update) {
      if (!empty($this->original) && $this->id() !== $this->original->id()) {
        // The old image style name needs flushing after a rename.
        $this->original->flush();
        // Update field instance settings if necessary.
        static::replaceImageStyle($this);
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
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $style) {
      // Flush cached media for the deleted style.
      $style->flush();
      // Check whether field instance settings need to be updated.
      // In case no replacement style was specified, all image fields that are
      // using the deleted style are left in a broken state.
      if ($new_id = $style->get('replacementID')) {
        // The deleted ID is still set as originalID.
        $style->set('name', $new_id);
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
    if ($style->id() != $style->getOriginalID()) {
      $instances = field_read_instances();
      // Loop through all fields searching for image fields.
      foreach ($instances as $instance) {
        if ($instance->getField()->type == 'image') {
          $view_modes = entity_get_view_modes($instance['entity_type']);
          $view_modes = array('default') + array_keys($view_modes);
          foreach ($view_modes as $view_mode) {
            $display = entity_get_display($instance['entity_type'], $instance['bundle'], $view_mode);
            $display_options = $display->getComponent($instance['field_name']);

            // Check if the formatter involves an image style.
            if ($display_options && $display_options['type'] == 'image' && $display_options['settings']['image_style'] == $style->getOriginalID()) {
              // Update display information for any instance using the image
              // style that was just deleted.
              $display_options['settings']['image_style'] = $style->id();
              $display->setComponent($instance['field_name'], $display_options)
                ->save();
            }
          }
          $entity_form_display = entity_get_form_display($instance['entity_type'], $instance['bundle'], 'default');
          $widget_configuration = $entity_form_display->getComponent($instance['field_name']);
          if ($widget_configuration['settings']['preview_image_style'] == $style->getOriginalID()) {
            $widget_options['settings']['preview_image_style'] = $style->id();
            $entity_form_display->setComponent($instance['field_name'], $widget_options)
              ->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deliver($scheme, $target) {
    $original_uri = $scheme . '://' . $target;

    // Check that the scheme is valid, and the image derivative token is valid.
    // (Sites which require image derivatives to be generated without a token
    // can set the 'image.settings:allow_insecure_derivatives' configuration to
    // TRUE to bypass the latter check, but this will increase the site's
    // vulnerability to denial-of-service attacks.)
    $valid = file_stream_wrapper_valid_scheme($scheme);
    if (!\Drupal::config('image.settings')->get('allow_insecure_derivatives')) {
      $image_derivative_token = \Drupal::request()->query->get(IMAGE_DERIVATIVE_TOKEN);
      $valid &= isset($image_derivative_token) && $image_derivative_token === $this->getPathToken($original_uri);
    }
    if (!$valid) {
      throw new AccessDeniedHttpException();
    }

    $derivative_uri = $this->buildUri($original_uri);
    $headers = array();

    // If using the private scheme, let other modules provide headers and
    // control access to the file.
    if ($scheme == 'private') {
      if (file_exists($derivative_uri)) {
        file_download($scheme, file_uri_target($derivative_uri));
      }
      else {
        $headers = \Drupal::moduleHandler()->invokeAll('file_download', array($original_uri));
        if (in_array(-1, $headers) || empty($headers)) {
          throw new AccessDeniedHttpException();
        }
      }
    }

    // Don't try to generate file if source is missing.
    if (!file_exists($original_uri)) {
      watchdog('image', 'Source image at %source_image_path not found while trying to generate derivative image at %derivative_path.',  array('%source_image_path' => $original_uri, '%derivative_path' => $derivative_uri));
      return new Response(t('Error generating image, missing source file.'), 404);
    }

    // Don't start generating the image if the derivative already exists or if
    // generation is in progress in another thread.
    $lock_name = 'image_style_deliver:' . $this->id() . ':' . Crypt::hashBase64($original_uri);
    if (!file_exists($derivative_uri)) {
      $lock_acquired = \Drupal::lock()->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, t('Image generation in progress. Try again shortly.'));
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = file_exists($derivative_uri) || $this->createDerivative($original_uri, $derivative_uri);

    if (!empty($lock_acquired)) {
      \Drupal::lock()->release($lock_name);
    }

    if ($success) {
      $image = image_load($derivative_uri);
      $uri = $image->source;
      $headers += array(
        'Content-Type' => $image->info['mime_type'],
        'Content-Length' => $image->info['file_size'],
      );
      return new BinaryFileResponse($uri, 200, $headers);
    }
    else {
      watchdog('image', 'Unable to generate the derived image located at %path.', array('%path' => $derivative_uri));
      return new Response(t('Error generating image.'), 500);
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
      $token_query = array(IMAGE_DERIVATIVE_TOKEN => $this->getPathToken(file_stream_wrapper_uri_normalize($path)));
    }

    if ($clean_urls === NULL) {
      // Assume clean URLs unless the request tells us otherwise.
      $clean_urls = TRUE;
      try {
        $request = \Drupal::request();
        $clean_urls = $request->attributes->get('clean_urls');
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
      $file_url .= (strpos($file_url, '?') !== FALSE ? '&' : '?') . Url::buildQuery($token_query);
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
      return;
    }

    // Delete the style directory in each registered wrapper.
    $wrappers = file_get_stream_wrappers(STREAM_WRAPPERS_WRITE_VISIBLE);
    foreach ($wrappers as $wrapper => $wrapper_data) {
      file_unmanaged_delete_recursive($wrapper . '://styles/' . $this->id());
    }

    // Let other modules update as necessary on flush.
    \Drupal::moduleHandler()->invokeAll('image_style_flush', array($this));

    // Clear field caches so that formatters may be added for this style.
    field_info_cache_clear();
    drupal_theme_rebuild();

    // Clear page caches when flushing.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      cache('block')->deleteAll();
    }
    cache('page')->deleteAll();
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

    if (!$image = image_load($original_uri)) {
      return FALSE;
    }

    if (!empty($this->effects)) {
      foreach ($this->effects as $effect) {
        image_effect_apply($image, $effect);
      }
    }

    if (!image_save($image, $derivative_uri)) {
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
    module_load_include('inc', 'image', 'image.effects');

    if (!empty($this->effects)) {
      foreach ($this->effects as $effect) {
        if (isset($effect['dimensions passthrough'])) {
          continue;
        }

        if (isset($effect['dimensions callback'])) {
          $effect['dimensions callback']($dimensions, $effect['data']);
        }
        else {
          $dimensions['width'] = $dimensions['height'] = NULL;
        }
      }
    }
  }

  /**
   * Generates a token to protect an image style derivative.
   *
   * This prevents unauthorized generation of an image style derivative,
   * which can be costly both in CPU time and disk space.
   *
   * @param string $uri
   *   The URI of the image for this style, for example as returned by
   *   \Drupal\image\ImageStyleInterface::buildUri().
   *
   * @return string
   *   An eight-character token which can be used to protect image style
   *   derivatives against denial-of-service attacks.
   */
  protected function getPathToken($uri) {
    // Return the first eight characters.
    return substr(Crypt::hmacBase64($this->id() . ':' . $uri, drupal_get_private_key() . drupal_get_hash_salt()), 0, 8);
  }

}
