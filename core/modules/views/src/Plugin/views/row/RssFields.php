<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\row\RssFields.
 */

namespace Drupal\views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;

/**
 * Renders an RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "rss_fields",
 *   title = @Translation("Fields"),
 *   help = @Translation("Display fields as RSS items."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RssFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['title_field'] = array('default' => '');
    $options['link_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['creator_field'] = array('default' => '');
    $options['date_field'] = array('default' => '');
    $options['guid_field_options']['contains']['guid_field'] = array('default' => '');
    $options['guid_field_options']['contains']['guid_field_is_permalink'] = array('default' => TRUE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = array('' => $this->t('- None -'));
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['title_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that is going to be used as the RSS item title for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    );
    $form['link_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#description' => $this->t('The field that is going to be used as the RSS item link for each row. This must be a drupal relative path.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['link_field'],
      '#required' => TRUE,
    );
    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#description' => $this->t('The field that is going to be used as the RSS item description for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['description_field'],
      '#required' => TRUE,
    );
    $form['creator_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Creator field'),
      '#description' => $this->t('The field that is going to be used as the RSS item creator for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['creator_field'],
      '#required' => TRUE,
    );
    $form['date_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Publication date field'),
      '#description' => $this->t('The field that is going to be used as the RSS item pubDate for each row. It needs to be in RFC 2822 format.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['date_field'],
      '#required' => TRUE,
    );
    $form['guid_field_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('GUID settings'),
      '#open' => TRUE,
    );
    $form['guid_field_options']['guid_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('GUID field'),
      '#description' => $this->t('The globally unique identifier of the RSS item.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['guid_field_options']['guid_field'],
      '#required' => TRUE,
    );
    $form['guid_field_options']['guid_field_is_permalink'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('GUID is permalink'),
      '#description' => $this->t('The RSS item GUID is a permalink.'),
      '#default_value' => $this->options['guid_field_options']['guid_field_is_permalink'],
    );
  }

  public function validate() {
    $errors = parent::validate();
    $required_options = array('title_field', 'link_field', 'description_field', 'creator_field', 'date_field');
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
        break;
      }
    }
    // Once more for guid.
    if (empty($this->options['guid_field_options']['guid_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
    }
    return $errors;
  }

  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    if (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = array();
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
      $this->view->style_plugin->namespaces += $xml_rdf_namespaces;
    }

    // Create the RSS item object.
    $item = new \stdClass();
    $item->title = $this->getField($row_index, $this->options['title_field']);
    $item->link = url($this->getField($row_index, $this->options['link_field']), array('absolute' => TRUE));
    $item->description = $this->getField($row_index, $this->options['description_field']);
    $item->elements = array(
      array('key' => 'pubDate', 'value' => $this->getField($row_index, $this->options['date_field'])),
      array(
        'key' => 'dc:creator',
        'value' => $this->getField($row_index, $this->options['creator_field']),
        'namespace' => array('xmlns:dc' => 'http://purl.org/dc/elements/1.1/'),
      ),
    );
    $guid_is_permalink_string = 'false';
    $item_guid = $this->getField($row_index, $this->options['guid_field_options']['guid_field']);
    if ($this->options['guid_field_options']['guid_field_is_permalink']) {
      $guid_is_permalink_string = 'true';
      $item_guid = url($item_guid, array('absolute' => TRUE));
    }
    $item->elements[] = array(
      'key' => 'guid',
      'value' => $item_guid,
      'attributes' => array('isPermaLink' => $guid_is_permalink_string),
    );

    $row_index++;

    foreach ($item->elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    );
    return drupal_render($build);
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
    return $this->view->style_plugin->getField($index, $field_id);
  }

}
