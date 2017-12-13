<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\argument\Date as NumericDate;

/**
 * Abstract argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see \Drupal\views\ManyTonOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("datetime")
 */
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match);

    $definition = $this->getFieldStorageDefinition();
    if ($definition->getSetting('datetime_type') === DateTimeItem::DATETIME_TYPE_DATE) {
      // Timezone offset calculation is not applicable to dates that are stored
      // as date-only.
      $this->calculateOffset = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDateField() {
    // Use string date storage/formatting since datetime fields are stored as
    // strings rather than UNIX timestamps.
    return $this->query->getDateField("$this->tableAlias.$this->realField", TRUE, $this->calculateOffset);
  }

  /**
   * {@inheritdoc}
   */
  public function getDateFormat($format) {
    // Pass in the string-field option.
    return $this->query->getDateFormat($this->getDateField(), $format, TRUE);
  }

}
