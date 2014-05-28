<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\row\RowPluginBase.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * @defgroup views_row_plugins Views row plugins
 * @{
 * Row plugins control how Views outputs an individual record.
 *
 * They are tightly coupled to style plugins, in that a style plugin is what
 * calls the row plugin.
 */

/**
 * Default plugin to view a single row of a table. This is really just a wrapper around
 * a theme function.
 */
abstract class RowPluginBase extends PluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = FALSE;

  /**
   * Returns the usesFields property.
   *
   * @return bool
   */
  function usesFields() {
    return $this->usesFields;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    if (isset($this->base_table)) {
      $options['relationship'] = array('default' => 'none');
    }

    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (isset($this->base_table)) {
      $executable = $form_state['view']->getExecutable();

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $executable->display_handler->getOption('relationships');
      $relationship_options = array();

      foreach ($relationships as $relationship) {
        $relationship_handler = Views::handlerManager('relationship')->getHandler($relationship);

        // If this relationship is valid for this type, add it to the list.
        $data = Views::viewsData()->get($relationship['table']);
        $base = $data[$relationship['field']]['relationship']['base'];
        if ($base == $this->base_table) {
          $relationship_handler->init($executable, $relationship);
          $relationship_options[$relationship['id']] = $relationship_handler->adminLabel();
        }
      }

      if (!empty($relationship_options)) {
        $relationship_options = array_merge(array('none' => t('Do not use a relationship')), $relationship_options);
        $rel = empty($this->options['relationship']) ? 'none' : $this->options['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
        }

        $form['relationship'] = array(
          '#type' => 'select',
          '#title' => t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
        );
      }
      else {
        $form['relationship'] = array(
          '#type' => 'value',
          '#value' => 'none',
        );
      }
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, &$form_state) { }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, &$form_state) { }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (isset($this->base_table)) {
      if (isset($this->options['relationship']) && isset($this->view->relationship[$this->options['relationship']])) {
        $relationship = $this->view->relationship[$this->options['relationship']];
        $this->field_alias = $this->view->query->addField($relationship->alias, $this->base_field);
      }
      else {
        $this->field_alias = $this->view->query->addField($this->base_table, $this->base_field);
      }
    }
  }

  /**
   * Allow the style to do stuff before each row is rendered.
   *
   * @param $result
   *   The full array of results from the query.
   */
  public function preRender($result) { }

  /**
   * Render a row object. This usually passes through to a theme template
   * of some form, but not always.
   *
   * @param object $row
   *   A single row of the query result, so an element of $view->result.
   *
   * @return string
   *   The rendered output of a single row, used by the style plugin.
   */
  public function render($row) {
    return array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $row,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    );
  }

}

/**
 * @}
 */
