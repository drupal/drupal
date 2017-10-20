<?php

namespace Drupal\system\Plugin\views\field;

@trigger_error(__NAMESPACE__ . '\BulkForm is deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use \Drupal\views\Plugin\views\field\BulkForm instead. See https://www.drupal.org/node/2916716.', E_USER_DEPRECATED);

use Drupal\views\Plugin\views\field\BulkForm as ViewsBulkForm;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @ViewsField("legacy_bulk_form")
 *
 * @deprecated in Drupal 8.5.x, will be removed before Drupal 9.0.0. Use
 *   \Drupal\views\Plugin\views\field\BulkForm instead.
 *
 * @see https://www.drupal.org/node/2916716
 */
class BulkForm extends ViewsBulkForm {

}
