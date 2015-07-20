<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\row\OpmlFields.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;

/**
 * Renders an OPML item based on fields.
 *
 * @ViewsRow(
 *   id = "opml_fields",
 *   title = @Translation("OPML fields"),
 *   help = @Translation("Display fields as OPML items."),
 *   theme = "views_view_row_opml",
 *   display_types = {"feed"}
 * )
 */
class OpmlFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['text_field'] = array('default' => '');
    $options['created_field'] = array('default' => '');
    $options['type_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['html_url_field'] = array('default' => '');
    $options['language_field'] = array('default' => '');
    $options['xml_url_field'] = array('default' => '');
    $options['url_field'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = array('' => $this->t('- None -'));
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $types = array(
      'rss' => $this->t('RSS'),
      'link' => $this->t('Link'),
      'include' => $this->t('Include'),
    );
    $types = array_merge($initial_labels, $types);
    $form['type_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type attribute'),
      '#description' => $this->t('The type of this row.'),
      '#options' => $types,
      '#default_value' => $this->options['type_field'],
    );
    $form['text_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Text attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML text attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['text_field'],
      '#required' => TRUE,
    );
    $form['created_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Created attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML created attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['created_field'],
    );
    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Description attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML description attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[type_field]"]' => array('value' => 'rss'),
        ),
      ),
    );
    $form['html_url_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('HTML URL attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML htmlUrl attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['html_url_field'],
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[type_field]"]' => array('value' => 'rss'),
        ),
      ),
    );
    $form['language_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML language attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['language_field'],
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[type_field]"]' => array('value' => 'rss'),
        ),
      ),
    );
    $form['xml_url_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('XML URL attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML text attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['xml_url_field'],
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[type_field]"]' => array('value' => 'rss'),
        ),
      ),
    );
    $form['url_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('URL attribute'),
      '#description' => $this->t('The field that is going to be used as the OPML url attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['url_field'],
      '#states' => array(
        'visible' => array(
          ':input[name="row_options[type_field]"]' => array(
            array('value' => 'link'),
            array('value' => 'include'),
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (empty($this->options['text_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views field to use for OPML text attribute.');
    }
    if (!empty($this->options['type_field'])) {
      if ($this->options['type_field'] == 'rss') {
        if (empty($this->options['xml_url_field'])) {
          $errors[] = $this->t('Row style plugin requires specifying which views field to use for XML URL attribute.');
        }
      }
      elseif (in_array($this->options['type_field'], array('link', 'include'))) {
        if (empty($this->options['url_field'])) {
          $errors[] = $this->t('Row style plugin requires specifying which views field to use for URL attribute.');
        }
      }
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    // Create the OPML item array.
    $item = array();
    $row_index = $this->view->row_index;
    $item['text'] = $this->getField($row_index, $this->options['text_field']);
    $item['created'] = $this->getField($row_index, $this->options['created_field']);
    if ($this->options['type_field']) {
      $item['type'] = $this->options['type_field'];
      if ($item['type'] == 'rss') {
        $item['description'] = $this->getField($row_index, $this->options['description_field']);
        $item['language'] = $this->getField($row_index, $this->options['language_field']);
        $item['xmlUrl'] = $this->getField($row_index, $this->options['xml_url_field']);
        $item['htmlUrl'] = $this->getField($row_index, $this->options['html_url_field']);
      }
      else {
        $item['url'] = $this->getField($row_index, $this->options['url_field']);
      }
    }
    // Remove empty attributes.
    $item = array_filter($item);

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    );
    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param $field_id
   *   The ID assigned to the required field in the display.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return (string) $this->view->style_plugin->getField($index, $field_id);
  }

}
