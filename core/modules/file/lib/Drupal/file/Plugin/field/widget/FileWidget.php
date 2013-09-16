<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\field\widget\FileWidget.
 */

namespace Drupal\file\Plugin\field\widget;

use Drupal\field\Annotation\FieldWidget;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "file_generic",
 *   label = @Translation("File"),
 *   field_types = {
 *     "file"
 *   },
 *   settings = {
 *     "progress_indicator" = "throbber"
 *   }
 * )
 */
class FileWidget extends WidgetBase {

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Progress indicator: @progress_indicator', array('@progress_indicator' => $this->getSetting('progress_indicator')));
    return $summary;
  }

  /**
   * Overrides \Drupal\field\Plugin\Type\Widget\WidgetBase::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldInterface $items, array &$form, array &$form_state) {
    $field_name = $this->fieldDefinition->getFieldName();
    $parents = $form['#parents'];

    // Load the items for form rebuilds from the field state as they might not be
    // in $form_state['values'] because of validation limitations. Also, they are
    // only passed in as $items when editing existing entities.
    $field_state = field_form_get_state($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }

    // Determine the number of widgets to display.
    $cardinality = $this->fieldDefinition->getFieldCardinality();
    switch ($cardinality) {
      case FIELD_CARDINALITY_UNLIMITED:
        $max = count($items);
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = check_plain($this->fieldDefinition->getFieldLabel());
    $description = field_filter_xss($this->fieldDefinition->getFieldDescription());

    $elements = array();

    $delta = 0;
    // Add an element for every existing item.
    foreach ($items as $item) {
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);

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
            '#default_value' => $item->_weight ?: $delta,
            '#weight' => 100,
          );
        }

        $elements[$delta] = $element;
        $delta++;
      }
    }

    $empty_single_allowed = ($cardinality == 1 && $delta == 0);
    $empty_multiple_allowed = ($cardinality == FIELD_CARDINALITY_UNLIMITED || $delta < $cardinality) && empty($form_state['programmed']);

    // Add one more empty row for new uploads except when this is a programmed
    // multiple form as it is not necessary.
    if ($empty_single_allowed || $empty_multiple_allowed) {
      $element = array(
        '#title' => $title,
        '#description' => $description,
      );
      $element = $this->formSingleElement($items, $delta, $element, $form, $form_state);
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
      $elements['#display_field'] = (bool) $this->getFieldSetting('display_field');

      // Add some properties that will eventually be added to the file upload
      // field. These are added here so that they may be referenced easily
      // through a hook_form_alter().
      $elements['#file_upload_title'] = t('Add a new file');
      $elements['#file_upload_description'] = array(
        '#theme' => 'file_upload_help',
        '#description' => '',
        '#upload_validators' => $elements[0]['#upload_validators'],
        '#cardinality' => $cardinality,
      );
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += array(
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    );

    $cardinality = $this->fieldDefinition->getFieldCardinality();
    $defaults = array(
      'fids' => array(),
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    );

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = element_info('managed_file');
    $element += array(
      '#type' => 'managed_file',
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#value_callback' => 'file_field_widget_value',
      '#process' => array_merge($element_info['#process'], array('file_field_widget_process')),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by file_field_widget_value() and
      // file_field_widget_process().
      '#display_field' => (bool) $field_settings['display_field'],
      '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      '#cardinality' => $cardinality,
    );

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      $items[$delta]->fids = array($items[$delta]->target_id);
    }
    $element['#default_value'] = $items[$delta]->getValue() + $defaults;

    $default_fids = $element['#extended'] ? $element['#default_value']['fids'] : $element['#default_value'];
    if (empty($default_fids)) {
      $file_upload_help = array(
        '#theme' => 'file_upload_help',
        '#description' => $element['#description'],
        '#upload_validators' => $element['#upload_validators'],
        '#cardinality' => $cardinality,
      );
      $element['#description'] = drupal_render($file_upload_help);
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = array('file_field_widget_multiple_count_validate');
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, array &$form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    $new_values = array();
    foreach ($values as &$value) {
      foreach ($value['fids'] as $fid) {
        $new_value = $value;
        $new_value['target_id'] = $fid;
        unset($new_value['fids']);
        $new_values[] = $new_value;
      }
    }

    return $new_values;
  }

}
