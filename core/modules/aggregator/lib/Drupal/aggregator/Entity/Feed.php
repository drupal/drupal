<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\Feed.
 */

namespace Drupal\aggregator\Entity;

use Drupal\Core\Entity\EntityNG;
use Symfony\Component\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\aggregator\FeedInterface;

/**
 * Defines the aggregator feed entity class.
 *
 * @EntityType(
 *   id = "aggregator_feed",
 *   label = @Translation("Aggregator feed"),
 *   module = "aggregator",
 *   controllers = {
 *     "storage" = "Drupal\aggregator\FeedStorageController",
 *     "render" = "Drupal\aggregator\FeedRenderController",
 *     "form" = {
 *       "default" = "Drupal\aggregator\FeedFormController",
 *       "delete" = "Drupal\aggregator\Form\FeedDeleteForm",
 *       "remove_items" = "Drupal\aggregator\Form\FeedItemsRemoveForm"
 *     }
 *   },
 *   base_table = "aggregator_feed",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "title",
 *   }
 * )
 */
class Feed extends EntityNG implements FeedInterface {

  /**
   * The feed ID.
   *
   * @todo rename to id.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $fid;

  /**
   * Title of the feed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $title;

  /**
   * The feed language code.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $langcode;

  /**
   * URL to the feed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $url;

  /**
   * How often to check for new feed items, in seconds.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $refresh;

  /**
   * Last time feed was checked for new items, as Unix timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $checked;

  /**
   * Time when this feed was queued for refresh, 0 if not queued.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $queued;

  /**
   * The parent website of the feed; comes from the <link> element in the feed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $link ;

  /**
   * The parent website's description;
   * comes from the <description> element in the feed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $description;

  /**
   * An image representing the feed.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $image;

  /**
   * Calculated hash of the feed data, used for validating cache.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $hash;

  /**
   * Entity tag HTTP response header, used for validating cache.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $etag;

  /**
   * When the feed was last modified, as a Unix timestamp.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $modified;

  /**
   * Number of items to display in the feed’s block.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $block;

  /**
   * Overrides Drupal\Core\Entity\EntityNG::init().
   */
  public function init() {
    parent::init();

    // We unset all defined properties, so magic getters apply.
    unset($this->fid);
    unset($this->title);
    unset($this->url);
    unset($this->refresh);
    unset($this->checked);
    unset($this->queued);
    unset($this->link);
    unset($this->description);
    unset($this->image);
    unset($this->hash);
    unset($this->etag);
    unset($this->modified);
    unset($this->block);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('fid')->value;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label($langcode = NULL) {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItems() {
    $manager = \Drupal::service('plugin.manager.aggregator.processor');
    foreach ($manager->getDefinitions() as $id => $definition) {
      $manager->createInstance($id)->remove($this);
    }
    // Reset feed.
    $this->checked->value = 0;
    $this->hash->value = '';
    $this->etag->value = '';
    $this->modified->value = 0;
    $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    $values += array(
      'link' => '',
      'description' => '',
      'image' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    // Invalidate the block cache to update aggregator feed-based derivatives.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
    $storage_controller->deleteCategories($entities);
    foreach ($entities as $entity) {
      // Notify processors to remove stored items.
      $manager = \Drupal::service('plugin.manager.aggregator.processor');
      foreach ($manager->getDefinitions() as $id => $definition) {
        $manager->createInstance($id)->remove($entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    foreach ($entities as $entity) {
      // Make sure there is no active block for this feed.
      $block_configs = config_get_storage_names_with_prefix('plugin.core.block');
      foreach ($block_configs as $config_id) {
        $config = \Drupal::config($config_id);
        if ($config->get('id') == 'aggregator_feed_block:' . $entity->id()) {
          $config->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    $this->clearBlockCacheDefinitions();
    $storage_controller->deleteCategories(array($this->id() => $this));
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = FALSE) {
    if (!empty($this->categories)) {
      $storage_controller->saveCategories($this, $this->categories);
    }
  }

  /**
   * Invalidate the block cache to update aggregator feed-based derivatives.
   */
  protected function clearBlockCacheDefinitions() {
    if ($block_manager = \Drupal::getContainer()->get('plugin.manager.block', Container::NULL_ON_INVALID_REFERENCE)) {
      $block_manager->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['fid'] = array(
      'label' => t('ID'),
      'description' => t('The ID of the aggregor feed.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $fields['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of the feed.'),
      'type' => 'string_field',
    );
    $fields['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The feed language code.'),
      'type' => 'language_field',
    );
    $fields['url'] = array(
      'label' => t('URL'),
      'description' => t('The URL to the feed.'),
      'type' => 'uri_field',
    );
    $fields['refresh'] = array(
      'label' => t('Refresh'),
      'description' => t('How often to check for new feed items, in seconds.'),
      'type' => 'integer_field',
    );
    $fields['checked'] = array(
      'label' => t('Checked'),
      'description' => t('Last time feed was checked for new items, as Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['queued'] = array(
      'label' => t('Queued'),
      'description' => t('Time when this feed was queued for refresh, 0 if not queued.'),
      'type' => 'integer_field',
    );
    $fields['link'] = array(
      'label' => t('Link'),
      'description' => t('The link of the feed.'),
      'type' => 'uri_field',
    );
    $fields['description'] = array(
      'label' => t('Description'),
      'description' => t("The parent website's description that comes from the !description element in the feed.", array('!description' => '<description>')),
      'type' => 'string_field',
    );
    $fields['image'] = array(
      'label' => t('image'),
      'description' => t('An image representing the feed.'),
      'type' => 'uri_field',
    );
    $fields['hash'] = array(
      'label' => t('Hash'),
      'description' => t('Calculated hash of the feed data, used for validating cache.'),
      'type' => 'string_field',
    );
    $fields['etag'] = array(
      'label' => t('Etag'),
      'description' => t('Entity tag HTTP response header, used for validating cache.'),
      'type' => 'string_field',
    );
    $fields['modified'] = array(
      'label' => t('Modified'),
      'description' => t('When the feed was last modified, as a Unix timestamp.'),
      'type' => 'integer_field',
    );
    $fields['block'] = array(
      'label' => t('Block'),
      'description' => t('Number of items to display in the feed’s block.'),
      'type' => 'integer_field',
    );
    return $fields;
  }

}
