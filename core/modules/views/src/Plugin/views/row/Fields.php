<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\row\Fields.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;

/**
 * The basic 'fields' row plugin
 *
 * This displays fields one after another, giving options for inline
 * or not.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "fields",
 *   title = @Translation("Fields"),
 *   help = @Translation("Displays the fields with an optional template."),
 *   theme = "views_view_fields",
 *   display_types = {"normal"}
 * )
 */
class Fields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['inline'] = array('default' => array());
    $options['separator'] = array('default' => '');
    $options['hide_empty'] = array('default' => FALSE);
    $options['default_field_elements'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide a form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $this->displayHandler->getFieldLabels();

    if (empty($this->options['inline'])) {
      $this->options['inline'] = array();
    }

    $form['default_field_elements'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Provide default field wrapper elements'),
      '#default_value' => $this->options['default_field_elements'],
      '#description' => $this->t('If not checked, fields that are not configured to customize their HTML elements will get no wrappers at all for their field, label and field + label wrappers. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
    );

    $form['inline'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Inline fields'),
      '#options' => $options,
      '#default_value' => $this->options['inline'],
      '#description' => $this->t('Inline fields will be displayed next to each other rather than one after another. Note that some fields will ignore this if they are block elements, particularly body fields and other formatted HTML.'),
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[default_field_elements]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['separator'] = array(
      '#title' => $this->t('Separator'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => isset($this->options['separator']) ? $this->options['separator'] : '',
      '#description' => $this->t('The separator may be placed between inline fields to keep them from squishing up next to each other. You can use HTML in this field.'),
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[default_field_elements]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['hide_empty'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Hide empty fields'),
      '#default_value' => $this->options['hide_empty'],
      '#description' => $this->t('Do not display fields, labels or markup for fields that are empty.'),
    );

  }

  /**
   * Perform any necessary changes to the form values prior to storage.
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $inline = $form_state->getValue(array('row_options', 'inline'));
    $form_state->setValue(array('row_options', 'inline'), array_filter($inline));
  }

}
