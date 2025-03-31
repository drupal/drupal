<?php

namespace Drupal\Core\Datetime\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Datetime\DateFormatInterface;
use Drupal\system\DateFormatAccessControlHandler;

/**
 * Defines the Date Format configuration entity class.
 */
#[ConfigEntityType(
  id: 'date_format',
  label: new TranslatableMarkup('Date format'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: ['access' => DateFormatAccessControlHandler::class],
  admin_permission: 'administer site configuration',
  list_cache_tags: ['rendered'],
  config_export: [
    'id',
    'label',
    'locked',
    'pattern',
  ],
  )]
class DateFormat extends ConfigEntityBase implements DateFormatInterface {

  /**
   * The date format machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the date format entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The date format pattern.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The locked status of this date format.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * Callback for uasort() to compare configuration entities.
   */
  public static function compare(ConfigEntityInterface $a, ConfigEntityInterface $b, \Collator $collator): int {
    if ($a->isLocked() == $b->isLocked()) {
      $a_label = $a->label();
      $b_label = $b->label();
      return $collator->compare($a_label, $b_label);
    }
    return $a->isLocked() ? 1 : -1;
  }

  /**
   * Helper callback for uasort() to sort configuration entities.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * \Drupal\Core\Config\Entity\ConfigEntityBase::sortEntities() instead.
   *
   * @see https://www.drupal.org/project/drupal/issues/2265487
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    @trigger_error(__CLASS__ . '::' . __FUNCTION__ . ' is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ' . __CLASS__ . '::sortEntities() instead. See https://www.drupal.org/project/drupal/issues/2265487', E_USER_DEPRECATED);
    if ($a->isLocked() == $b->isLocked()) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return $a->isLocked() ? 1 : -1;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return ['rendered'];
  }

}
