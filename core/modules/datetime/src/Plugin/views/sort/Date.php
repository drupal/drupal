<?php

namespace Drupal\datetime\Plugin\views\sort;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\views\Attribute\ViewsSort;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\sort\Date as NumericDate;

/**
 * Basic sort handler for datetime fields.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 */
#[ViewsSort("datetime")]
class Date extends NumericDate {

  use FieldAPIHandlerTrait;

  /**
   * Determines if the timezone offset is calculated.
   *
   * @var bool
   */
  protected $calculateOffset = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $definition = $this->getFieldStorageDefinition();
    if ($definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      // Timezone offset calculation is not applicable to dates that are stored
      // as date-only.
      $this->calculateOffset = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Override to account for dates stored as strings.
   */
  public function getDateField() {
    // Use string date storage/formatting since datetime fields are stored as
    // strings rather than UNIX timestamps.
    return $this->query->getDateField("$this->tableAlias.$this->realField", TRUE, $this->calculateOffset);
  }

  /**
   * {@inheritdoc}
   *
   * Overridden in order to pass in the string date flag.
   */
  public function getDateFormat($format) {
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

}
