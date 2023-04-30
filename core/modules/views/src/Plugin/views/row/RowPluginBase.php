<?php

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Views;

/**
 * @defgroup views_row_plugins Views row plugins
 * @{
 * Plugins that control how Views outputs an individual record.
 *
 * Row plugins handle rendering each individual record from the view results.
 * For instance, a row plugin could render fields, render an entire entity
 * in a particular view mode, or render the raw data from the results.
 *
 * Row plugins are used by some (but not all) style plugins. They are not
 * activated unless the style plugin sets them up. See the
 * @link views_style_plugins Views style plugins topic @endlink for
 * more information.
 *
 * Row plugins extend \Drupal\views\Plugin\views\row\RowPluginBase. They must
 * be annotated with \Drupal\views\Annotation\ViewsRow annotation, and
 * they must be in namespace directory Plugin\views\row.
 *
 * @ingroup views_plugins
 * @see plugin_api
 */

/**
 * Base class for Views row plugins.
 *
 * This is really just a wrapper around a theme hook. It renders a row
 * of the result table by putting it into a render array with the set theme
 * hook.
 */
abstract class RowPluginBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * Does the row plugin support to add fields to its output.
   *
   * @var bool
   */
  protected $usesFields = FALSE;

  /**
   * Returns the usesFields property.
   *
   * @return bool
   */
  public function usesFields() {
    return $this->usesFields;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    if (isset($this->base_table)) {
      $options['relationship'] = ['default' => 'none'];
    }

    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (isset($this->base_table)) {
      $executable = $form_state->get('view')->getExecutable();

      // A whole bunch of code to figure out what relationships are valid for
      // this item.
      $relationships = $executable->display_handler->getOption('relationships');
      $relationship_options = [];

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
        $relationship_options = array_merge(['none' => $this->t('Do not use a relationship')], $relationship_options);
        $rel = empty($this->options['relationship']) ? 'none' : $this->options['relationship'];
        if (empty($relationship_options[$rel])) {
          // Pick the first relationship.
          $rel = key($relationship_options);
        }

        $form['relationship'] = [
          '#type' => 'select',
          '#title' => $this->t('Relationship'),
          '#options' => $relationship_options,
          '#default_value' => $rel,
        ];
      }
      else {
        $form['relationship'] = [
          '#type' => 'value',
          '#value' => 'none',
        ];
      }
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {}

  /**
   * Perform any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {}

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
  public function preRender($result) {}

  /**
   * Renders a row object.
   *
   * This usually passes through to a theme template of some form, but not
   * always.
   *
   * @param object $row
   *   A single row of the query result, so an element of $view->result.
   *
   * @return string
   *   The rendered output of a single row, used by the style plugin.
   */
  public function render($row) {
    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $row,
      '#field_alias' => $this->field_alias ?? '',
    ];
  }

}

/**
 * @}
 */
