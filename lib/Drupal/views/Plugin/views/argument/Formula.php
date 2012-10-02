<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Formula.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\ViewExecutable;

/**
 * Abstract argument handler for simple formulae.
 *
 * Child classes of this object should implement summary_argument, at least.
 *
 * Definition terms:
 * - formula: The formula to use for this handler.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "formula"
 * )
 */
class Formula extends ArgumentPluginBase {

  var $formula = NULL;

  /**
   * Overrides Drupal\views\Plugin\views\argument\ArgumentPluginBase::init().
   */
  public function init(ViewExecutable $view, &$options) {
    parent::init($view, $options);

    if (!empty($this->definition['formula'])) {
      $this->formula = $this->definition['formula'];
    }
  }

  function get_formula() {
    return str_replace('***table***', $this->tableAlias, $this->formula);
  }

  /**
   * Build the summary query based on a formula
   */
  function summary_query() {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $formula = $this->get_formula();

    // Add the field.
    $this->base_alias = $this->name_alias = $this->query->add_field(NULL, $formula, $this->field);
    $this->query->set_count_field(NULL, $formula, $this->field);

    return $this->summary_basics(FALSE);
  }

  /**
   * Build the query based upon the formula
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $placeholder = $this->placeholder();
    $formula = $this->get_formula() .' = ' . $placeholder;
    $placeholders = array(
      $placeholder => $this->argument,
    );
    $this->query->add_where(0, $formula, $placeholders, 'formula');
  }

}
