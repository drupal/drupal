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
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
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
