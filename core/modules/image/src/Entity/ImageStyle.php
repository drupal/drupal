<?php

namespace Drupal\image\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Site\Settings;
use Drupal\image\Event\ImageDerivativePipelineEvents;
use Drupal\image\Event\ImageStyleEvent;
use Drupal\image\Event\ImageStyleEvents;
use Drupal\image\ImageEffectPluginCollection;
use Drupal\image\ImageEffectInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Defines an image style configuration entity.
 *
 * @ConfigEntityType(
 *   id = "image_style",
 *   label = @Translation("Image style"),
 *   label_collection = @Translation("Image styles"),
 *   label_singular = @Translation("image style"),
 *   label_plural = @Translation("image styles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count image style",
 *     plural = "@count image styles",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\image\Form\ImageStyleAddForm",
 *       "edit" = "Drupal\image\Form\ImageStyleEditForm",
 *       "delete" = "Drupal\image\Form\ImageStyleDeleteForm",
 *       "flush" = "Drupal\image\Form\ImageStyleFlushForm"
 *     },
 *     "list_builder" = "Drupal\image\ImageStyleListBuilder",
 *     "storage" = "Drupal\image\ImageStyleStorage",
 *   },
 *   admin_permission = "administer image styles",
 *   config_prefix = "style",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   links = {
 *     "flush-form" = "/admin/config/media/image-styles/manage/{image_style}/flush",
 *     "edit-form" = "/admin/config/media/image-styles/manage/{image_style}",
 *     "delete-form" = "/admin/config/media/image-styles/manage/{image_style}/delete",
 *     "collection" = "/admin/config/media/image-styles",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "effects",
 *   }
 * )
 */
class ImageStyle extends ConfigEntityBase implements ImageStyleInterface, EntityWithPluginCollectionInterface {

  /**
   * The name of the image style.
   *
   * @var string
   */
  protected $name;

  /**
   * The image style label.
   *
   * @var string
   */
  protected $label;

  /**
   * The array of image effects for this image style.
   *
   * @var array
   */
  protected $effects = [];

  /**
   * Holds the collection of image effects that are used by this image style.
   *
   * @var \Drupal\image\ImageEffectPluginCollection
   */
  protected $effectsCollection;

  /**
   * {@inheritdoc}
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
        // Update field settings if necessary.
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

    /** @var \Drupal\image\ImageStyleInterface[] $entities */
    foreach ($entities as $style) {
      // Flush cached media for the deleted style.
      $style->flush();
      // Clear the replacement ID, if one has been previously stored.
      /** @var \Drupal\image\ImageStyleStorageInterface $storage */
      $storage->clearReplacementId($style->id());
    }
  }

  /**
   * Update field settings if the image style name is changed.
   *
   * @param \Drupal\image\ImageStyleInterface $style
   *   The image style.
   */
  protected static function replaceImageStyle(ImageStyleInterface $style) {
    if ($style->id() != $style->getOriginalId()) {
      // Loop through all entity displays looking for formatters / widgets using
      // the image style.
      foreach (EntityViewDisplay::loadMultiple() as $display) {
        foreach ($display->getComponents() as $name => $options) {
          if (isset($options['type']) && $options['type'] == 'image' && $options['settings']['image_style'] == $style->getOriginalId()) {
            $options['settings']['image_style'] = $style->id();
            $display->setComponent($name, $options)
              ->save();
          }
        }
      }
      foreach (EntityFormDisplay::loadMultiple() as $display) {
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
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageUri($uri)
      ->getDerivativeImageUri();
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl($path, $clean_urls = NULL) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageUri($path)
      ->setCleanUrl($clean_urls)
      ->getDerivativeImageUrl()
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function flush($path = NULL) {
    if (isset($path)) {
      // A specific image path has been provided. Flush only that derivative.
      $pipeline = \Drupal::service('image.processor')->createInstance('derivative')
        ->setImageStyle($this)
        ->setSourceImageUri($path)
        ->dispatch(ImageDerivativePipelineEvents::REMOVE_DERIVATIVE_IMAGE);
    }
    else {
      \Drupal::service('event_dispatcher')->dispatch(new ImageStyleEvent($this), ImageStyleEvents::FLUSH);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createDerivative($original_uri, $derivative_uri) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageUri($original_uri)
      ->setDerivativeImageUri($derivative_uri)
      ->buildDerivativeImage();
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    $pipeline = \Drupal::service('image.processor')->createInstance('derivative');
    $pipeline
      ->setImageStyle($this)
      ->setSourceImageUri($uri)
      ->setSourceImageDimensions($dimensions['width'] ?? NULL, $dimensions['height'] ?? NULL)
      ->dispatch(ImageDerivativePipelineEvents::RESOLVE_DERIVATIVE_IMAGE_DIMENSIONS);
    $dimensions['width'] = $pipeline->getVariable('derivativeImageWidth');
    $dimensions['height'] = $pipeline->getVariable('derivativeImageHeight');
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeExtension($extension) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageFileExtension($extension)
      ->getDerivativeImageFileExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function getPathToken($uri) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageUri($uri)
      ->getDerivativeImageUrlSecurityToken();
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
  public function supportsUri($uri) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.processor')->createInstance('derivative')
      ->setImageStyle($this)
      ->setSourceImageUri($uri)
      ->isSourceImageProcessable();
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
    if (!$this->effectsCollection) {
      $this->effectsCollection = new ImageEffectPluginCollection($this->getImageEffectPluginManager(), $this->effects);
      $this->effectsCollection->sort();
    }
    return $this->effectsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['effects' => $this->getEffects()];
  }

  /**
   * {@inheritdoc}
   */
  public function addImageEffect(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getEffects()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacementID() {
    /** @var \Drupal\image\ImageStyleStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());
    return $storage->getReplacementId($this->id());
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

  /**
   * Returns the image effect plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The image effect plugin manager.
   */
  protected function getImageEffectPluginManager() {
    return \Drupal::service('plugin.manager.image.effect');
  }

  /**
   * Returns the image factory.
   *
   * @return \Drupal\Core\Image\ImageFactory
   *   The image factory.
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function getImageFactory() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('image.factory');
  }

  /**
   * Gets the Drupal private key.
   *
   * @return string
   *   The Drupal private key.
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function getPrivateKey() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('private_key')->get();
  }

  /**
   * Gets a salt useful for hardening against SQL injection.
   *
   * @return string
   *   A salt based on information in settings.php, not in the database.
   *
   * @throws \RuntimeException
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function getHashSalt() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return Settings::getHashSalt();
  }

  /**
   * Adds an extension to a path.
   *
   * If this image style changes the extension of the derivative, this method
   * adds the new extension to the given path. This way we avoid filename
   * clashes while still allowing us to find the source image.
   *
   * @param string $path
   *   The path to add the extension to.
   *
   * @return string
   *   The given path if this image style doesn't change its extension, or the
   *   path with the added extension if it does.
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function addExtension($path) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    $original_extension = pathinfo($path, PATHINFO_EXTENSION);
    $extension = $this->getDerivativeExtension($original_extension);
    if ($original_extension !== $extension) {
      $path .= '.' . $extension;
    }
    return $path;
  }

  /**
   * Provides a wrapper to allow unit testing.
   *
   * Gets the default file stream implementation.
   *
   * @return string
   *   'public', 'private' or any other file scheme defined as the default.
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function fileDefaultScheme() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::config('system.file')->get('default_scheme');
  }

  /**
   * Gets the stream wrapper manager service.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   *   The stream wrapper manager service
   *
   * @todo deprecated since version 9.x.x and will be removed in y.y.y.
   */
  protected function getStreamWrapperManager() {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 9.x.x and will be removed in y.y.y.', E_USER_DEPRECATED);
    return \Drupal::service('stream_wrapper_manager');
  }

}
