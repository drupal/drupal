<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\filter\Combine.
 */

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("combine")
 */
class Combine extends StringFilter {

  /**
   * @var views_plugin_query_default
   */
  var $query;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['fields'] = array('default' => array());

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $this->view->initStyle();

    // Allow to choose all fields as possible
    if ($this->view->style_plugin->usesFields()) {
      $options = array();
      foreach ($this->view->display_handler->getHandlers('field') as $name => $field) {
        $options[$name] = $field->adminLabel(TRUE);
      }
      if ($options) {
        $form['fields'] = array(
          '#type' => 'select',
          '#title' => $this->t('Choose fields to combine for filtering'),
          '#description' => $this->t("This filter doesn't work for very special field handlers."),
          '#multiple' => TRUE,
          '#options' => $options,
          '#default_value' => $this->options['fields'],
        );
      }
      else {
        $form_state->setErrorByName('', $this->t('You have to add some fields to be able to use this filter.'));
      }
    }
  }

  public function query() {
    $this->view->_build('field');
    $fields = array();
    // Only add the fields if they have a proper field and table alias.
    foreach ($this->options['fields'] as $id) {
      // Overridden fields can lead to fields missing from a display that are
      // still set in the non-overridden combined filter.
      if (!isset($this->view->field[$id])) {
        // If fields are no longer available that are needed to filter by, make
        // sure no results are shown to prevent displaying more then intended.
        $this->view->build_info['fail'] = TRUE;
        continue;
      }
      $field = $this->view->field[$id];
      // Always add the table of the selected fields to be sure a table alias exists.
      $field->ensureMyTable();
      if (!empty($field->field_alias) && !empty($field->field_alias)) {
        $fields[] = "$field->tableAlias.$field->realField";
      }
    }
    if ($fields) {
      $count = count($fields);
      $separated_fields = array();
      foreach ($fields as $key => $field) {
        $separated_fields[] = $field;
        if ($key < $count-1) {
          $separated_fields[] = "' '";
        }
      }
      $expression = implode(', ', $separated_fields);
      $expression = "CONCAT_WS(' ', $expression)";

      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($expression);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $fields = $this->view->display_handler->getHandlers('field');
    foreach ($this->options['fields'] as $id) {
      if (!isset($fields[$id])) {
        // Combined field filter only works with fields that are in the field
        // settings.
        $errors[] = $this->t('Field %field set in %filter is not set in this display.', array('%field' => $id, '%filter' => $this->adminLabel()));
        break;
      }
    }
    return $errors;
  }

  // By default things like opEqual uses add_where, that doesn't support
  // complex expressions, so override all operators.

  function opEqual($expression) {
    $placeholder = $this->placeholder();
    $operator = $this->operator();
    $this->query->addWhereExpression($this->options['group'], "$expression $operator $placeholder", array($placeholder => $this->value));
  }

  protected function opContains($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", array($placeholder => '%' . db_like($this->value) . '%'));
  }

  protected function opStartsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", array($placeholder => db_like($this->value) . '%'));
  }

  protected function opNotStartsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", array($placeholder => db_like($this->value) . '%'));
  }

  protected function opEndsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression LIKE $placeholder", array($placeholder => '%' . db_like($this->value)));
  }

  protected function opNotEndsWith($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", array($placeholder => '%' . db_like($this->value)));
  }

  protected function opNotLike($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression NOT LIKE $placeholder", array($placeholder => '%' . db_like($this->value) . '%'));
  }

  protected function opRegex($expression) {
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression($this->options['group'], "$expression REGEXP $placeholder", array($placeholder => $this->value));
  }

  protected function opEmpty($expression) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhereExpression($this->options['group'], "$expression $operator");
  }

}
