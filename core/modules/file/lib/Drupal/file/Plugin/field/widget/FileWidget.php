<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\widget\FileWidget.
 */

namespace Drupal\file\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @Plugin(
 *   id = "file_generic",
 *   module = "file",
 *   label = @Translation("File"),
 *   field_types = {
 *     "file"
 *   },
 *   settings = {
 *     "progress_indicator" = "throbber"
 *   },
 *   default_value = FALSE
 * )
 */
class FileWidget extends WidgetBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::settingsForm().
   */
  public function settingsForm(array $form, array &$form_state) {
    $element['progress_indicator'] = array(
      '#type' => 'radios',
      '#title' => t('Progress indicator'),
      '#options' => array(
        'throbber' => t('Throbber'),
        'bar' => t('Bar with progress meter'),
      ),
      '#default_value' => $this->getSetting('progress_indicator'),
      '#description' => t('The throbber display does not show the status of uploads but takes up less space. The progress bar is helpful for monitoring progress on large uploads.'),
      '#weight' => 16,
      '#access' => file_progress_implementation(),
    );
    return $element;
  }

  /**
   * Overrides \Drupal\field\Plugin\Type\Widget\WidgetBase::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(EntityInterface $entity, array $items, $langcode, array &$form, array &$form_state) {
    $field = $this->field;
    $instance = $this->instance;
    $field_name = $field['field_name'];

    $parents = $form['#parents'];

    // Load the items for form rebuilds from the field state as they might not be
    // in $form_state['values'] because of validation limitations. Also, they are
    // only passed in as $items when editing existing entities.
    $field_state = field_form_get_state($parents, $field_name, $langcode, $form_state);
    if (isset($field_state['items'])) {
      $items = $field_state['items'];
    }

    // Determine the number of widgets to display.
    switch ($field['cardinality']) {
      case FIELD_CARDINALITY_UNLIMITED:
        $max = count($items);
        $is_multiple = TRUE;
        break;

      default:
        $max = $field['cardinality'] - 1;
        $is_multiple = ($field['cardinality'] > 1);
        break;
    }

    $id_prefix = implode('-', array_merge($parents, array($field_name)));
    $wrapper_id = drupal_html_id($id_prefix . '-add-more-wrapper');

    $title = check_plain($instance['label']);
    $description = field_filter_xss($instance['description']);

    $elements = array();

    $delta = 0;
    // Add an element for every existing item.
    foreach ($items as $item) {
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($entity, $items, $delta, $langcode, $element, $form, $form_state);

      if ($element) {
        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {
          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = array(
            '#type' => 'weight',
            '#title' => t('Weight for row @number', array('@number' => $delta + 1)),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => isset($item['_weight']) ? $item['_weight'] : $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
        $delta++;
      }
    }

    $empty_single_allowed = ($this->field['cardinality'] == 1 && $delta == 0);
    $empty_multiple_allowed = ($this->field['cardinality'] == FIELD_CARDINALITY_UNLIMITED || $delta < $this->field['cardinality']) && empty($form_state['programmed']);

    // Add one more empty row for new uploads except when this is a programmed
    // multiple form as it is not necessary.
    if ($empty_single_allowed || $empty_multiple_allowed) {
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($entity, $items, $delta, $langcode, $element, $form, $form_state);
      if ($element) {
        $element['#required'] = ($element['#required'] && $delta == 0);
        $elements[$delta] = $element;
      }
    }

    if ($is_multiple) {
      // The group of elements all-together need some extra functionality after
      // building up the full list (like draggable table rows).
      $elements['#file_upload_delta'] = $delta;
      $elements['#type'] = 'details';
      $elements['#theme'] = 'file_widget_multiple';
      $elements['#theme_wrappers'] = array('details');
      $elements['#process'] = array('file_field_widget_process_multiple');
      $elements['#title'] = $title;

      $elements['#description'] = $description;
      $elements['#field_name'] = $element['#field_name'];
      $elements['#language'] = $element['#language'];
      $elements['#display_field'] = !empty($this->field['settings']['display_field']);

      // Add some properties that will eventually be added to the file upload
      // field. These are added here so that they may be referenced easily
      // through a hook_form_alter().
      $elements['#file_upload_title'] = t('Add a new file');
      $elements['#file_upload_description'] = theme('file_upload_help', array('description' => '', 'upload_validators' => $elements[0]['#upload_validators'], 'cardinality' => $this->field['cardinality']));
    }

    return $elements;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $defaults = array(
      'fids' => array(),
      'display' => !empty($this->field['settings']['display_default']),
      'description' => '',
    );

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = element_info('managed_file');
    $element += array(
      '#type' => 'managed_file',
      '#upload_location' => file_field_widget_uri($this->field, $this->instance),
      '#upload_validators' => file_field_widget_upload_validators($this->field, $this->instance),
      '#value_callback' => 'file_field_widget_value',
      '#process' => array_merge($element_info['#process'], array('file_field_widget_process')),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
    );

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]['fids']) && isset($items[$delta]['fid'])) {
      $items[$delta]['fids'][0] = $items[$delta]['fid'];
    }
    $element['#default_value'] = !empty($items[$delta]) ? $items[$delta] : $defaults;

    $default_fids = $element['#extended'] ? $element['#default_value']['fids'] : $element['#default_value'];
    if (empty($default_fids)) {
      $cardinality = isset($this->field['cardinality']) ? $this->field['cardinality'] : 1;
      $element['#description'] = theme('file_upload_help', array('description' => $element['#description'], 'upload_validators' => $element['#upload_validators'], 'cardinality' => $cardinality));
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = array('file_field_widget_multiple_count_validate');
      }
    }

    return $element;
  }

  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::massageFormValues().
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    $new_values = array();
    foreach ($values as &$value) {
      foreach ($value['fids'] as $fid) {
        $new_value = $value;
        $new_value['fid'] = $fid;
        unset($new_value['fids']);
        $new_values[] = $new_value;
      }
    }

    return $new_values;
  }

}
