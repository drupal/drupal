<?php

namespace Drupal\views\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Argument handler for simple formulae.
 *
 * Child classes of this object should implement summaryArgument, at least.
 *
 * Definition terms:
 * - formula: The formula to use for this handler.
 *
 * @ingroup views_argument_handlers
  */
#[ViewsArgument(
  id: 'formula',
)]
class Formula extends ArgumentPluginBase {

  public $formula = NULL;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->definition['formula'])) {
      $this->formula = $this->definition['formula'];
    }
  }

  public function getFormula() {
    return str_replace('***table***', $this->tableAlias, $this->formula);
  }

  /**
   * Build the summary query based on a formula.
   */
  protected function summaryQuery() {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $formula = $this->getFormula();

    // Add the field.
    $this->base_alias = $this->name_alias = $this->query->addField(NULL, $formula, $this->field);
    $this->query->setCountField(NULL, $formula, $this->field);

    return $this->summaryBasics(FALSE);
  }

  /**
   * Build the query based upon the formula.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    // Now that our table is secure, get our formula.
    $placeholder = $this->placeholder();
    $formula = $this->getFormula() . ' = ' . $placeholder;
    $placeholders = [
      $placeholder => $this->argument,
    ];
    $this->query->addWhere(0, $formula, $placeholders, 'formula');
  }

}
