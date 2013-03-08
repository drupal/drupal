<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\row\DataFieldRow.
 */

namespace Drupal\rest\Plugin\views\row;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "data_field",
 *   module = "rest",
 *   title = @Translation("Fields"),
 *   help = @Translation("Use fields as row data."),
 *   type = "data"
 * )
 */
class DataFieldRow extends RowPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::$usesFields.
   */
  protected $usesFields = TRUE;

  /**
   * Stores an array of prepared field aliases from options.
   *
   * @var array
   */
  protected $replacementAliases = array();

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['aliases'])) {
      // Prepare a trimmed version of replacement aliases.
      $this->replacementAliases = array_filter(array_map('trim', (array) $this->options['aliases']));
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['aliases'] = array('default' => array());

    return $options;
  }


  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['aliases'] = array(
      '#type' => 'fieldset',
      '#title' => t('Field ID aliases'),
      '#description' => t('Rename views default field IDs in the output data.'),
      '#tree' => TRUE,
    );

    if ($fields = $this->view->display_handler->getOption('fields')) {
      foreach ($fields as $id => $field) {
        $form['aliases'][$id] = array(
          '#type' => 'textfield',
          '#title' => $id,
          '#default_value' => isset($this->options['aliases'][$id]) ? $this->options['aliases'][$id] : '',
          '#element_validate' => array(array($this, 'validateAliasName')),
        );
      }
    }
  }

  /**
   * Form element validation handler for \Drupal\rest\Plugin\views\row\DataFieldRow::buildOptionsForm().
   */
  public function validateAliasName($element, &$form_state) {
    if (preg_match('@[^A-Za-z0-9_-]+@', $element['#value'])) {
      form_error($element, t('The machine-readable name must contain only letters, numbers, dashes and underscores.'));
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, &$form_state) {
    $aliases = $form_state['values']['row_options']['aliases'];
    // If array filter returns empty, no values have been entered. Unique keys
    // should only be validated if we have some.
    if (array_filter($aliases) && (array_unique($aliases) !== $aliases)) {
      form_set_error('aliases', t('All field aliases must be unique'));
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\row\RowPluginBase::render().
   */
  public function render($row) {
    $output = array();

    foreach ($this->view->field as $id => $field) {
      // If we don't have a field alias, Just try to get the rendered output
      // from the field.
      if ($field->field_alias == 'unknown') {
        $value = $field->render($row);
      }
      // Get the value directly from the result row.
      else {
        $value = $row->{$field->field_alias};
      }

      $output[$this->getFieldKeyAlias($id)] = $value;
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

}
