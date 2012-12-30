<?php

/**
 * @file
 * Definition of Drupal\edit\Ajax\FieldRenderedWithoutTransformationFiltersCommand.
 */

namespace Drupal\edit\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to rerender a processed text field without any transformation
 * filters.
 */
class FieldRenderedWithoutTransformationFiltersCommand extends BaseCommand {

  /**
   * Constructs a FieldRenderedWithoutTransformationFiltersCommand object.
   *
   * @param string $data
   *   The data to pass on to the client side.
   */
  public function __construct($data) {
    parent::__construct('editFieldRenderedWithoutTransformationFilters', $data);
  }

}
