<?php

/**
 * @file
 * Contains \Drupal\aggregator\Entity\Feed.
 */

namespace Drupal\aggregator\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Symfony\Component\DependencyInjection\Container;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\aggregator\FeedInterface;

/**
 * Defines the aggregator feed entity class.
 *
 * @ContentEntityType(
 *   id = "aggregator_feed",
 *   label = @Translation("Aggregator feed"),
 *   controllers = {
 *     "storage" = "Drupal\aggregator\FeedStorageController",
 *     "view_builder" = "Drupal\aggregator\FeedViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\aggregator\FeedFormController",
 *       "delete" = "Drupal\aggregator\Form\FeedDeleteForm",
 *       "remove_items" = "Drupal\aggregator\Form\FeedItemsRemoveForm",
 *     }
 *   },
 *   links = {
 *     "canonical" = "aggregator.feed_view",
 *     "edit-form" = "aggregator.feed_configure",
 *     "delete-form" = "aggregator.feed_delete",
 *   },
 *   base_table = "aggregator_feed",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class Feed extends ContentEntityBase implements FeedInterface {

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('fid')->value;
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::label().
   */
  public function label() {
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
    $this->setLastCheckedTime(0);
    $this->setHash('');
    $this->setEtag('');
    $this->setLastModified(0);
    $this->save();

    return $this;
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
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      // Make sure there are no active blocks for these feeds.
      $ids = \Drupal::entityQuery('block')
        ->condition('plugin', 'aggregator_feed_block')
        ->condition('settings.feed', array_keys($entities))
        ->execute();
      if ($ids) {
        $block_storage = \Drupal::entityManager()->getStorageController('block');
        $block_storage->delete($block_storage->loadMultiple($ids));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['fid'] = FieldDefinition::create('integer')
      ->setLabel(t('Feed ID'))
      ->setDescription(t('The ID of the aggregator feed.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The aggregator feed UUID.'))
      ->setReadOnly(TRUE);

    $fields['title'] = FieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the feed.'));

    $fields['langcode'] = FieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The feed language code.'));

    $fields['url'] = FieldDefinition::create('uri')
      ->setLabel(t('URL'))
      ->setDescription(t('The URL to the feed.'));

    $fields['refresh'] = FieldDefinition::create('integer')
      ->setLabel(t('Refresh'))
      ->setDescription(t('How often to check for new feed items, in seconds.'));

    $fields['checked'] = FieldDefinition::create('timestamp')
      ->setLabel(t('Checked'))
      ->setDescription(t('Last time feed was checked for new items, as Unix timestamp.'));

    $fields['queued'] = FieldDefinition::create('timestamp')
      ->setLabel(t('Queued'))
      ->setDescription(t('Time when this feed was queued for refresh, 0 if not queued.'));

    $fields['link'] = FieldDefinition::create('uri')
      ->setLabel(t('Link'))
      ->setDescription(t('The link of the feed.'));

    $fields['description'] = FieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t("The parent website's description that comes from the !description element in the feed.", array('!description' => '<description>')));

    $fields['image'] = FieldDefinition::create('uri')
      ->setLabel(t('Image'))
      ->setDescription(t('An image representing the feed.'));

    $fields['hash'] = FieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Calculated hash of the feed data, used for validating cache.'));

    $fields['etag'] = FieldDefinition::create('string')
      ->setLabel(t('Etag'))
      ->setDescription(t('Entity tag HTTP response header, used for validating cache.'));

    // This is updated by the fetcher and not when the feed is saved, therefore
    // it's a timestamp and not a changed field.
    $fields['modified'] = FieldDefinition::create('timestamp')
      ->setLabel(t('Modified'))
      ->setDescription(t('When the feed was last modified, as a Unix timestamp.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshRate() {
    return $this->get('refresh')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastCheckedTime() {
    return $this->get('checked')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueuedTime() {
    return $this->get('queued')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsiteUrl() {
    return $this->get('link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return $this->get('image')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEtag() {
    return $this->get('etag')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastModified() {
    return $this->get('modified')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->set('url', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshRate($refresh) {
    $this->set('refresh', $refresh);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastCheckedTime($checked) {
    $this->set('checked', $checked);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueuedTime($queued) {
    $this->set('queued', $queued);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebsiteUrl($link) {
    $this->set('link', $link);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setImage($image) {
    $this->set('image', $image);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEtag($etag) {
    $this->set('etag', $etag);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastModified($modified) {
    $this->set('modified', $modified);
    return $this;
  }

}
