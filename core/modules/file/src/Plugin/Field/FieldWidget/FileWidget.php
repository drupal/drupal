<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldWidget\FileWidget.
 */

namespace Drupal\file\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'file_generic' widget.
 *
 * @FieldWidget(
 *   id = "file_generic",
 *   label = @Translation("File"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->elementInfo = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition,$configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('element_info'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'progress_indicator' => 'throbber',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
   * Overrides \Drupal\Core\Field\WidgetBase::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];

    // Load the items for form rebuilds from the field state as they might not
    // be in $form_state->getValues() because of validation limitations. Also,
    // they are only passed in as $items when editing existing entities.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }

    // Determine the number of widgets to display.
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $max = count($items);
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    $title = SafeMarkup::checkPlain($this->fieldDefinition->getLabel());
    $description = $this->fieldFilterXss($this->fieldDefinition->getDescription());

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
    $empty_multiple_allowed = ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $delta < $cardinality) && !$form_state->isProgrammed();

    // Add one more empty row for new uploads except when this is a programmed
    // multiple form as it is not necessary.
    if ($empty_single_allowed || $empty_multiple_allowed) {
      // Create a new empty item.
      $items->appendItem();
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
      $elements['#open'] = TRUE;
      $elements['#theme'] = 'file_widget_multiple';
      $elements['#theme_wrappers'] = array('details');
      $elements['#process'] = array(array(get_class($this), 'processMultiple'));
      $elements['#title'] = $title;

      $elements['#description'] = $description;
      $elements['#field_name'] = $field_name;
      $elements['#language'] = $items->getLangcode();
      $elements['#display_field'] = (bool) $this->getFieldSetting('display_field');
      // The field settings include defaults for the field type. However, this
      // widget is a base class for other widgets (e.g., ImageWidget) that may
      // act on field types without these expected settings.
      $field_settings = $this->getFieldSettings() + array('display_field' => NULL);
      $elements['#display_field'] = (bool) $field_settings['display_field'];

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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += array(
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    );

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = array(
      'fids' => array(),
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    );

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('managed_file');
    $element += array(
      '#type' => 'managed_file',
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#value_callback' => array(get_class($this), 'value'),
      '#process' => array_merge($element_info['#process'], array(array(get_class($this), 'process'))),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
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
      $element['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = array(array(get_class($this), 'validateMultipleCount'));
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
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

  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input = FALSE, FormStateInterface $form_state) {
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      if (empty($input['display'])) {
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
    }

    // We depend on the managed file element to handle uploads.
    $return = ManagedFile::valueCallback($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += array(
      'fids' => array(),
      'display' => 1,
      'description' => '',
    );

    return $return;
  }

  /**
   * Form element validation callback for upload element on file widget. Checks
   * if user has uploaded more files than allowed.
   *
   * This validator is used only when cardinality not set to 1 or unlimited.
   */
  public static function validateMultipleCount($element, FormStateInterface $form_state, $form) {
    $parents = $element['#parents'];
    $values = NestedArray::getValue($form_state->getValues(), $parents);

    array_pop($parents);
    $current = count(Element::children(NestedArray::getValue($form, $parents))) - 1;

    $field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions($element['#entity_type']);
    $field_storage = $field_storage_definitions[$element['#field_name']];
    $uploaded = count($values['fids']);
    $count = $uploaded + $current;
    if ($count > $field_storage->getCardinality()) {
      $keep = $uploaded - $count + $field_storage->getCardinality();
      $removed_files = array_slice($values['fids'], $keep);
      $removed_names = array();
      foreach ($removed_files as $fid) {
        $file = File::load($fid);
        $removed_names[] = $file->getFilename();
      }
      $args = array('%field' => $field_storage->getFieldName(), '@max' => $field_storage->getCardinality(), '@count' => $keep, '%list' => implode(', ', $removed_names));
      $message = t('Field %field can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.', $args);
      drupal_set_message($message, 'warning');
      $values['fids'] = array_slice($values['fids'], 0, $keep);
      NestedArray::setValue($form_state->getValues(), $element['#parents'], $values);
    }
  }

  /**
   * Form API callback: Processes a file_generic field element.
   *
   * Expands the file_generic type to include the description and display
   * fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $item = $element['#value'];
    $item['fids'] = $element['fids']['#value'];

    $element['#theme'] = 'file_widget';

    // Add the display field if enabled.
    if ($element['#display_field']) {
      $element['display'] = array(
        '#type' => empty($item['fids']) ? 'hidden' : 'checkbox',
        '#title' => t('Include file in display'),
        '#attributes' => array('class' => array('file-display')),
      );
      if (isset($item['display'])) {
        $element['display']['#value'] = $item['display'] ? '1' : '';
      } else {
        $element['display']['#value'] = $element['#display_default'];
      }
    }
    else {
      $element['display'] = array(
        '#type' => 'hidden',
        '#value' => '1',
      );
    }

    // Add the description field if enabled.
    if ($element['#description_field'] && $item['fids']) {
      $config = \Drupal::config('file.settings');
      $element['description'] = array(
        '#type' => $config->get('description.type'),
        '#title' => t('Description'),
        '#value' => isset($item['description']) ? $item['description'] : '',
        '#maxlength' => $config->get('description.length'),
        '#description' => t('The description may be used as the label of the link to the file.'),
      );
    }

    // Adjust the Ajax settings so that on upload and remove of any individual
    // file, the entire group of file fields is updated together.
    if ($element['#cardinality'] != 1) {
      $parents = array_slice($element['#array_parents'], 0, -1);
      $new_options = array(
        'query' => array(
          'element_parents' => implode('/', $parents),
        ),
      );
      $field_element = NestedArray::getValue($form, $parents);
      $new_wrapper = $field_element['#id'] . '-ajax-wrapper';
      foreach (Element::children($element) as $key) {
        if (isset($element[$key]['#ajax'])) {
          $element[$key]['#ajax']['options'] = $new_options;
          $element[$key]['#ajax']['wrapper'] = $new_wrapper;
        }
      }
      unset($element['#prefix'], $element['#suffix']);
    }

    // Add another submit handler to the upload and remove buttons, to implement
    // functionality needed by the field widget. This submit handler, along with
    // the rebuild logic in file_field_widget_form() requires the entire field,
    // not just the individual item, to be valid.
    foreach (array('upload_button', 'remove_button') as $key) {
      $element[$key]['#submit'][] = array(get_called_class(), 'submit');
      $element[$key]['#limit_validation_errors'] = array(array_slice($element['#parents'], 0, -1));
    }

    return $element;
  }

  /**
   * Form API callback: Processes a group of file_generic field elements.
   *
   * Adds the weight field to each row so it can be ordered and adds a new Ajax
   * wrapper around the entire group so it can be replaced all at once.
   *
   * This method on is assigned as a #process callback in formMultipleElements()
   * method.
   */
  public static function processMultiple($element, FormStateInterface $form_state, $form) {
    $element_children = Element::children($element, TRUE);
    $count = count($element_children);

    // Count the number of already uploaded files, in order to display new
    // items in \Drupal\file\Element\ManagedFile::uploadAjaxCallback().
    if (!$form_state->isRebuilding()) {
      $count_items_before = 0;
      foreach ($element_children as $children) {
        if (!empty($element[$children]['#default_value']['fids'])) {
          $count_items_before++;
        }
      }

      $form_state->set('file_upload_delta_initial', $count_items_before);
    }

    foreach ($element_children as $delta => $key) {
      if ($key != $element['#file_upload_delta']) {
        $description = static::getDescriptionFromElement($element[$key]);
        $element[$key]['_weight'] = array(
          '#type' => 'weight',
          '#title' => $description ? t('Weight for @title', array('@title' => $description)) : t('Weight for new file'),
          '#title_display' => 'invisible',
          '#delta' => $count,
          '#default_value' => $delta,
        );
      }
      else {
        // The title needs to be assigned to the upload field so that validation
        // errors include the correct widget label.
        $element[$key]['#title'] = $element['#title'];
        $element[$key]['_weight'] = array(
          '#type' => 'hidden',
          '#default_value' => $delta,
        );
      }
    }

    // Add a new wrapper around all the elements for Ajax replacement.
    $element['#prefix'] = '<div id="' . $element['#id'] . '-ajax-wrapper">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Retrieves the file description from a field field element.
   *
   * This helper static method is used by processMultiple() method.
   *
   * @param array $element
   *   An associative array with the element being processed.
   *
   * @return array|false
   *   A description of the file suitable for use in the administrative
   *   interface.
   */
  protected static function getDescriptionFromElement($element) {
    // Use the actual file description, if it's available.
    if (!empty($element['#default_value']['description'])) {
      return $element['#default_value']['description'];
    }
    // Otherwise, fall back to the filename.
    if (!empty($element['#default_value']['filename'])) {
      return $element['#default_value']['filename'];
    }
    // This is probably a newly uploaded file; no description is available.
    return FALSE;
  }

  /**
   * Form submission handler for upload/remove button of formElement().
   *
   * This runs in addition to and after file_managed_file_submit().
   *
   * @see file_managed_file_submit()
   */
  public static function submit($form, FormStateInterface $form_state) {
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -2);
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $submitted_values = NestedArray::getValue($form_state->getValues(), array_slice($button['#parents'], 0, -2));
    foreach ($submitted_values as $delta => $submitted_value) {
      if (empty($submitted_value['fids'])) {
        unset($submitted_values[$delta]);
      }
    }

    // If there are more files uploaded via the same widget, we have to separate
    // them, as we display each file in it's own widget.
    $new_values = array();
    foreach ($submitted_values as $delta => $submitted_value) {
      if (is_array($submitted_value['fids'])) {
        foreach ($submitted_value['fids'] as $fid) {
          $new_value = $submitted_value;
          $new_value['fids'] = array($fid);
          $new_values[] = $new_value;
        }
      }
      else {
        $new_value = $submitted_value;
      }
    }

    // Re-index deltas after removing empty items.
    $submitted_values = array_values($new_values);

    // Update form_state values.
    NestedArray::setValue($form_state->getValues(), array_slice($button['#parents'], 0, -2), $submitted_values);

    // Update items.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items'] = $submitted_values;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

}
