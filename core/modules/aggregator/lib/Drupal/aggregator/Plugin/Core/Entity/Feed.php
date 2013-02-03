<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\Core\Entity\Feed.
 */

namespace Drupal\aggregator\Plugin\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the aggregator feed entity class.
 *
 * @Plugin(
 *   id = "aggregator_feed",
 *   label = @Translation("Aggregator feed"),
 *   module = "aggregator",
 *   controller_class = "Drupal\aggregator\FeedStorageController",
 *   render_controller_class = "Drupal\aggregator\FeedRenderController",
 *   form_controller_class = {
 *     "default" = "Drupal\aggregator\FeedFormController"
 *   },
 *   base_table = "aggregator_feed",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "title",
 *   }
 * )
 */
class Feed extends EntityNG implements ContentEntityInterface {

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
}
