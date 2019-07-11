<?php

namespace Drupal\rest\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "data_field",
 *   title = @Translation("Fields"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"data"}
 * )
 */
class DataFieldRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * Stores an array of prepared field aliases from options.
   *
   * @var array
   */
  protected $replacementAliases = [];

  /**
   * Stores an array of options to determine if the raw field output is used.
   *
   * @var array
   */
  protected $rawOutputOptions = [];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['field_options'])) {
      $options = (array) $this->options['field_options'];
      // Prepare a trimmed version of replacement aliases.
      $aliases = static::extractFromOptionsArray('alias', $options);
      $this->replacementAliases = array_filter(array_map('trim', $aliases));
      // Prepare an array of raw output field options.
      $this->rawOutputOptions = static::extractFromOptionsArray('raw_output', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_options'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['field_options'] = [
      '#type' => 'table',
      '#header' => [$this->t('Field'), $this->t('Alias'), $this->t('Raw output')],
      '#empty' => $this->t('You have no fields. Add some to your view.'),
      '#tree' => TRUE,
    ];

    $options = $this->options['field_options'];

    if ($fields = $this->view->display_handler->getOption('fields')) {
      foreach ($fields as $id => $field) {
        // Don't show the field if it has been excluded.
        if (!empty($field['exclude'])) {
          continue;
        }
        $form['field_options'][$id]['field'] = [
          '#markup' => $id,
        ];
        $form['field_options'][$id]['alias'] = [
          '#title' => $this->t('Alias for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => isset($options[$id]['alias']) ? $options[$id]['alias'] : '',
          '#element_validate' => [[$this, 'validateAliasName']],
        ];
        $form['field_options'][$id]['raw_output'] = [
          '#title' => $this->t('Raw output for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => isset($options[$id]['raw_output']) ? $options[$id]['raw_output'] : '',
        ];
      }
    }
  }

  /**
   * Form element validation handler for \Drupal\rest\Plugin\views\row\DataFieldRow::buildOptionsForm().
   */
  public function validateAliasName($element, FormStateInterface $form_state) {
    if (preg_match('@[^A-Za-z0-9_-]+@', $element['#value'])) {
      $form_state->setError($element, $this->t('The machine-readable name must contain only letters, numbers, dashes and underscores.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    // Collect an array of aliases to validate.
    $aliases = static::extractFromOptionsArray('alias', $form_state->getValue(['row_options', 'field_options']));

    // If array filter returns empty, no values have been entered. Unique keys
    // should only be validated if we have some.
    if (($filtered = array_filter($aliases)) && (array_unique($filtered) !== $filtered)) {
      $form_state->setErrorByName('aliases', $this->t('All field aliases must be unique'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $output = [];

    foreach ($this->view->field as $id => $field) {
      // If the raw output option has been set, just get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = $field->getValue($row);
      }
      // Otherwise, get rendered field.
      else {
        // Advanced render for token replacement.
        $markup = $field->advancedRender($row);
        // Post render to support uncacheable fields.
        $field->postRender($row, $markup);
        $value = $field->last_render;
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($id)] = $value;
      }
    }

    return $output;
  }

  /**
   * Return an alias for a field ID, as set in the options form.
   *
   * @param string $id
   *   The field id to lookup an alias for.
   *
   * @return string
   *   The matches user entered alias, or the original ID if nothing is found.
   */
  public function getFieldKeyAlias($id) {
    if (isset($this->replacementAliases[$id])) {
      return $this->replacementAliases[$id];
    }

    return $id;
  }

  /**
   * Extracts a set of option values from a nested options array.
   *
   * @param string $key
   *   The key to extract from each array item.
   * @param array $options
   *   The options array to return values from.
   *
   * @return array
   *   A regular one dimensional array of values.
   */
  protected static function extractFromOptionsArray($key, $options) {
    return array_map(function ($item) use ($key) {
      return isset($item[$key]) ? $item[$key] : NULL;
    }, $options);
  }

}
