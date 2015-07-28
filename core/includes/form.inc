<?php

/**
 * @file
 * Functions for form and batch generation and processing.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormElementHelper;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Prepares variables for select element templates.
 *
 * Default template: select.html.twig.
 *
 * It is possible to group options together; to do this, change the format of
 * $options to an associative array in which the keys are group labels, and the
 * values are associative arrays in the normal $options format.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #extra,
 *     #multiple, #required, #name, #attributes, #size.
 */
function template_preprocess_select(&$variables) {
  $element = $variables['element'];
  Element::setAttributes($element, array('id', 'name', 'size'));
  Element\RenderElement::setAttributes($element, array('form-select'));

  $variables['attributes'] = $element['#attributes'];
  $variables['options'] = form_select_options($element);
}

/**
 * Converts an options form element into a structured array for output.
 *
 * This function calls itself recursively to obtain the values for each optgroup
 * within the list of options and when the function encounters an object with
 * an 'options' property inside $element['#options'].
 *
 * @param array $element
 *   An associative array containing the following key-value pairs:
 *   - #multiple: Optional Boolean indicating if the user may select more than
 *     one item.
 *   - #options: An associative array of options to render as HTML. Each array
 *     value can be a string, an array, or an object with an 'option' property:
 *     - A string or integer key whose value is a translated string is
 *       interpreted as a single HTML option element. Do not use placeholders
 *       that sanitize data: doing so will lead to double-escaping. Note that
 *       the key will be visible in the HTML and could be modified by malicious
 *       users, so don't put sensitive information in it.
 *     - A translated string key whose value is an array indicates a group of
 *       options. The translated string is used as the label attribute for the
 *       optgroup. Do not use placeholders to sanitize data: doing so will lead
 *       to double-escaping. The array should contain the options you wish to
 *       group and should follow the syntax of $element['#options'].
 *     - If the function encounters a string or integer key whose value is an
 *       object with an 'option' property, the key is ignored, the contents of
 *       the option property are interpreted as $element['#options'], and the
 *       resulting HTML is added to the output.
 *   - #value: Optional integer, string, or array representing which option(s)
 *     to pre-select when the list is first displayed. The integer or string
 *     must match the key of an option in the '#options' list. If '#multiple' is
 *     TRUE, this can be an array of integers or strings.
 * @param array|null $choices
 *   (optional) Either an associative array of options in the same format as
 *   $element['#options'] above, or NULL. This parameter is only used internally
 *   and is not intended to be passed in to the initial function call.
 *
 * @return mixed[]
 *   A structured, possibly nested, array of options and optgroups for use in a
 *   select form element.
 *   - label: A translated string whose value is the text of a single HTML
 *     option element, or the label attribute for an optgroup.
 *   - options: Optional, array of options for an optgroup.
 *   - selected: A boolean that indicates whether the option is selected when
 *     rendered.
 *   - type: A string that defines the element type. The value can be 'option'
 *     or 'optgroup'.
 *   - value: A string that contains the value attribute for the option.
 */
function form_select_options($element, $choices = NULL) {
  if (!isset($choices)) {
    if (empty($element['#options'])) {
      return [];
    }
    $choices = $element['#options'];
  }
  // array_key_exists() accommodates the rare event where $element['#value'] is NULL.
  // isset() fails in this situation.
  $value_valid = isset($element['#value']) || array_key_exists('#value', $element);
  $value_is_array = $value_valid && is_array($element['#value']);
  // Check if the element is multiple select and no value has been selected.
  $empty_value = (empty($element['#value']) && !empty($element['#multiple']));
  $options = [];
  foreach ($choices as $key => $choice) {
    if (is_array($choice)) {
      $options[] = [
        'type' => 'optgroup',
        'label' => $key,
        'options' => form_select_options($element, $choice),
      ];
    }
    elseif (is_object($choice) && isset($choice->option)) {
      $options = array_merge($options, form_select_options($element, $choice->option));
    }
    else {
      $option = [];
      $key = (string) $key;
      $empty_choice = $empty_value && $key == '_none';
      if ($value_valid && ((!$value_is_array && (string) $element['#value'] === $key || ($value_is_array && in_array($key, $element['#value']))) || $empty_choice)) {
        $option['selected'] = TRUE;
      }
      else {
        $option['selected'] = FALSE;
      }
      $option['type'] = 'option';
      $option['value'] = $key;
      $option['label'] = $choice;
      $options[] = $option;
    }
  }
  return $options;
}

/**
 * Returns the indexes of a select element's options matching a given key.
 *
 * This function is useful if you need to modify the options that are
 * already in a form element; for example, to remove choices which are
 * not valid because of additional filters imposed by another module.
 * One example might be altering the choices in a taxonomy selector.
 * To correctly handle the case of a multiple hierarchy taxonomy,
 * #options arrays can now hold an array of objects, instead of a
 * direct mapping of keys to labels, so that multiple choices in the
 * selector can have the same key (and label). This makes it difficult
 * to manipulate directly, which is why this helper function exists.
 *
 * This function does not support optgroups (when the elements of the
 * #options array are themselves arrays), and will return FALSE if
 * arrays are found. The caller must either flatten/restore or
 * manually do their manipulations in this case, since returning the
 * index is not sufficient, and supporting this would make the
 * "helper" too complicated and cumbersome to be of any help.
 *
 * As usual with functions that can return array() or FALSE, do not
 * forget to use === and !== if needed.
 *
 * @param $element
 *   The select element to search.
 * @param $key
 *   The key to look for.
 *
 * @return
 *   An array of indexes that match the given $key. Array will be
 *   empty if no elements were found. FALSE if optgroups were found.
 */
function form_get_options($element, $key) {
  $keys = array();
  foreach ($element['#options'] as $index => $choice) {
    if (is_array($choice)) {
      return FALSE;
    }
    elseif (is_object($choice)) {
      if (isset($choice->option[$key])) {
        $keys[] = $index;
      }
    }
    elseif ($index == $key) {
      $keys[] = $index;
    }
  }
  return $keys;
}

/**
 * Prepares variables for fieldset element templates.
 *
 * Default template: fieldset.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes, #children, #description, #id, #title,
 *     #value.
 */
function template_preprocess_fieldset(&$variables) {
  $element = $variables['element'];
  Element::setAttributes($element, array('id'));
  Element\RenderElement::setAttributes($element);
  $variables['attributes'] = $element['#attributes'];
  $variables['prefix'] = isset($element['#field_prefix']) ? $element['#field_prefix'] : NULL;
  $variables['suffix'] = isset($element['#field_suffix']) ? $element['#field_suffix'] : NULL;
  $variables['title_display'] = isset($element['#title_display']) ? $element['#title_display'] : NULL;
  $variables['children'] = $element['#children'];
  $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;

  $variables['legend']['title'] = (isset($element['#title']) && $element['#title'] !== '') ? Xss::filterAdmin($element['#title']) : '';
  $variables['legend']['attributes'] = new Attribute();
  $variables['legend_span']['attributes'] = new Attribute();

  if (!empty($element['#description'])) {
    $description_id = $element['#attributes']['id'] . '--description';
    $description_attributes['id'] = $description_id;
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = $element['#description'];

    // Add the description's id to the fieldset aria attributes.
    $variables['attributes']['aria-describedby'] = $description_id;
  }

  // Display any error messages.
  $variables['errors'] = NULL;
  if (!empty($element['#errors']) && empty($element['#error_no_message'])) {
    $variables['errors'] = $element['#errors'];
  }
}

/**
 * Prepares variables for details element templates.
 *
 * Default template: details.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes, #children, #open,
 *     #description, #id, #title, #value, #optional.
 */
function template_preprocess_details(&$variables) {
  $element = $variables['element'];
  $variables['attributes'] = $element['#attributes'];
  $variables['summary_attributes'] = new Attribute();
  if (!empty($element['#title'])) {
    $variables['summary_attributes']['role'] = 'button';
    if (!empty($element['#attributes']['id'])) {
      $variables['summary_attributes']['aria-controls'] = $element['#attributes']['id'];
    }
    $variables['summary_attributes']['aria-expanded'] = !empty($element['#attributes']['open']) ? 'true' : 'false';
    $variables['summary_attributes']['aria-pressed'] = $variables['summary_attributes']['aria-expanded'];
  }
  $variables['title'] = (!empty($element['#title'])) ? $element['#title'] : '';
  $variables['description'] = (!empty($element['#description'])) ? $element['#description'] : '';
  $variables['children'] = (isset($element['#children'])) ? $element['#children'] : '';
  $variables['value'] = (isset($element['#value'])) ? $element['#value'] : '';
}

/**
 * Prepares variables for radios templates.
 *
 * Default template: radios.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes, #children.
 */
function template_preprocess_radios(&$variables) {
  $element = $variables['element'];
  $variables['attributes'] = array();
  if (isset($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }
  if (isset($element['#attributes']['title'])) {
    $variables['attributes']['title'] = $element['#attributes']['title'];
  }
  $variables['children'] = $element['#children'];
}

/**
 * Prepares variables for checkboxes templates.
 *
 * Default template: checkboxes.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #children, #attributes.
 */
function template_preprocess_checkboxes(&$variables) {
  $element = $variables['element'];
  $variables['attributes'] = array();
  if (isset($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }
  if (isset($element['#attributes']['title'])) {
    $variables['attributes']['title'] = $element['#attributes']['title'];
  }
  $variables['children'] = $element['#children'];
}

/**
 * Prepares variables for vertical tabs templates.
 *
 * Default template: vertical-tabs.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 *
 */
function template_preprocess_vertical_tabs(&$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for input templates.
 *
 * Default template: input.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes.
 */
function template_preprocess_input(&$variables) {
  $element = $variables['element'];
  $variables['children'] = $element['#children'];
}

/**
 * Prepares variables for form templates.
 *
 * Default template: form.html.twig.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #action, #method, #attributes, #children
 */
function template_preprocess_form(&$variables) {
  $element = $variables['element'];
  if (isset($element['#action'])) {
    $element['#attributes']['action'] = UrlHelper::stripDangerousProtocols($element['#action']);
  }
  Element::setAttributes($element, array('method', 'id'));
  if (empty($element['#attributes']['accept-charset'])) {
    $element['#attributes']['accept-charset'] = "UTF-8";
  }
  $variables['attributes'] = $element['#attributes'];
  $variables['children'] = $element['#children'];
}

/**
 * Prepares variables for textarea templates.
 *
 * Default template: textarea.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #description, #rows, #cols,
 *     #placeholder, #required, #attributes, #resizable
 *
 */
function template_preprocess_textarea(&$variables) {
  $element = $variables['element'];
  Element::setAttributes($element, array('id', 'name', 'rows', 'cols', 'placeholder'));
  Element\RenderElement::setAttributes($element, array('form-textarea'));
  $variables['wrapper_attributes'] = new Attribute();
  $variables['attributes'] = new Attribute($element['#attributes']);
  $variables['value'] = SafeMarkup::checkPlain($element['#value']);
  $variables['resizable'] = !empty($element['#resizable']) ? $element['#resizable'] : NULL;
  $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;
}

/**
 * Returns HTML for a form element.
 * Prepares variables for form element templates.
 *
 * Default template: form-element.html.twig.
 *
 * In addition to the element itself, the DIV contains a label for the element
 * based on the optional #title_display property, and an optional #description.
 *
 * The optional #title_display property can have these values:
 * - before: The label is output before the element. This is the default.
 *   The label includes the #title and the required marker, if #required.
 * - after: The label is output after the element. For example, this is used
 *   for radio and checkbox #type elements. If the #title is empty but the field
 *   is #required, the label will contain only the required marker.
 * - invisible: Labels are critical for screen readers to enable them to
 *   properly navigate through forms but can be visually distracting. This
 *   property hides the label for everyone except screen readers.
 * - attribute: Set the title attribute on the element to create a tooltip
 *   but output no label element. This is supported only for checkboxes
 *   and radios in
 *   \Drupal\Core\Render\Element\CompositeFormElementTrait::preRenderCompositeFormElement().
 *   It is used where a visual label is not needed, such as a table of
 *   checkboxes where the row and column provide the context. The tooltip will
 *   include the title and required marker.
 *
 * If the #title property is not set, then the label and any required marker
 * will not be output, regardless of the #title_display or #required values.
 * This can be useful in cases such as the password_confirm element, which
 * creates children elements that have their own labels and required markers,
 * but the parent element should have neither. Use this carefully because a
 * field without an associated label can cause accessibility challenges.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #title_display, #description, #id, #required,
 *     #children, #type, #name.
 */
function template_preprocess_form_element(&$variables) {
  $element = &$variables['element'];

  // This function is invoked as theme wrapper, but the rendered form element
  // may not necessarily have been processed by
  // \Drupal::formBuilder()->doBuildForm().
  $element += array(
    '#title_display' => 'before',
    '#wrapper_attributes' => array(),
  );
  $variables['attributes'] = $element['#wrapper_attributes'];

  // Add element #id for #type 'item' and 'password_confirm'.
  if (isset($element['#markup']) && !empty($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }

  // Pass elements #type and #name to template.
  if (!empty($element['#type'])) {
    $variables['type'] = $element['#type'];
  }
  if (!empty($element['#name'])) {
    $variables['name'] = $element['#name'];
  }

  // Pass elements disabled status to template.
  $variables['disabled'] = !empty($element['#attributes']['disabled']) ? $element['#attributes']['disabled'] : NULL;

  // Display any error messages.
  $variables['errors'] = NULL;
  if (!empty($element['#errors']) && empty($element['#error_no_message'])) {
    $variables['errors'] = $element['#errors'];
  }

  // If #title is not set, we don't display any label.
  if (!isset($element['#title'])) {
    $element['#title_display'] = 'none';
  }

  $variables['title_display'] = $element['#title_display'];

  $variables['prefix'] = isset($element['#field_prefix']) ? $element['#field_prefix'] : NULL;
  $variables['suffix'] = isset($element['#field_suffix']) ? $element['#field_suffix'] : NULL;

  $variables['description'] = NULL;
  if (!empty($element['#description'])) {
    $variables['description_display'] = $element['#description_display'];
    $description_attributes = [];
    if (!empty($element['#id'])) {
      $description_attributes['id'] = $element['#id'] . '--description';
    }
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = $element['#description'];
  }

  // Add label_display and label variables to template.
  $variables['label_display'] = $element['#title_display'];
  $variables['label'] = array('#theme' => 'form_element_label');
  $variables['label'] += array_intersect_key($element, array_flip(array('#id', '#required', '#title', '#title_display')));

  $variables['children'] = $element['#children'];
}

/**
 * Prepares variables for form label templates.
 *
 * Form element labels include the #title and a #required marker. The label is
 * associated with the element itself by the element #id. Labels may appear
 * before or after elements, depending on form-element.html.twig and
 * #title_display.
 *
 * This function will not be called for elements with no labels, depending on
 * #title_display. For elements that have an empty #title and are not required,
 * this function will output no label (''). For required elements that have an
 * empty #title, this will output the required marker alone within the label.
 * The label will use the #id to associate the marker with the field that is
 * required. That is especially important for screenreader users to know
 * which field is required.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #required, #title, #id, #value, #description.
 */
function template_preprocess_form_element_label(&$variables) {
  $element = $variables['element'];
  // If title and required marker are both empty, output no label.
  $variables['title'] = (isset($element['#title']) && $element['#title'] !== '') ? Xss::filterAdmin($element['#title']) : '';
  $variables['attributes'] = array();

  // Pass elements title_display to template.
  $variables['title_display'] = $element['#title_display'];

  // A #for property of a dedicated #type 'label' element as precedence.
  if (!empty($element['#for'])) {
    $variables['attributes']['for'] = $element['#for'];
    // A custom #id allows the referenced form input element to refer back to
    // the label element; e.g., in the 'aria-labelledby' attribute.
    if (!empty($element['#id'])) {
      $variables['attributes']['id'] = $element['#id'];
    }
  }
  // Otherwise, point to the #id of the form input element.
  elseif (!empty($element['#id'])) {
    $variables['attributes']['for'] = $element['#id'];
  }

  // Pass elements required to template.
  $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;
}

/**
 * @defgroup batch Batch operations
 * @{
 * Creates and processes batch operations.
 *
 * Functions allowing forms processing to be spread out over several page
 * requests, thus ensuring that the processing does not get interrupted
 * because of a PHP timeout, while allowing the user to receive feedback
 * on the progress of the ongoing operations.
 *
 * The API is primarily designed to integrate nicely with the Form API
 * workflow, but can also be used by non-Form API scripts (like update.php)
 * or even simple page callbacks (which should probably be used sparingly).
 *
 * Example:
 * @code
 * $batch = array(
 *   'title' => t('Exporting'),
 *   'operations' => array(
 *     array('my_function_1', array($account->id(), 'story')),
 *     array('my_function_2', array()),
 *   ),
 *   'finished' => 'my_finished_callback',
 *   'file' => 'path_to_file_containing_myfunctions',
 * );
 * batch_set($batch);
 * // Only needed if not inside a form _submit handler.
 * // Setting redirect in batch_process.
 * batch_process('node/1');
 * @endcode
 *
 * Note: if the batch 'title', 'init_message', 'progress_message', or
 * 'error_message' could contain any user input, it is the responsibility of
 * the code calling batch_set() to sanitize them first with a function like
 * \Drupal\Component\Utility\SafeMarkup::checkPlain() or
 * \Drupal\Component\Utility\Xss::filter(). Furthermore, if the batch operation
 * returns any user input in the 'results' or 'message' keys of $context, it
 * must also sanitize them first.
 *
 * Sample callback_batch_operation():
 * @code
 * // Simple and artificial: load a node of a given type for a given user
 * function my_function_1($uid, $type, &$context) {
 *   // The $context array gathers batch context information about the execution (read),
 *   // as well as 'return values' for the current operation (write)
 *   // The following keys are provided :
 *   // 'results' (read / write): The array of results gathered so far by
 *   //   the batch processing, for the current operation to append its own.
 *   // 'message' (write): A text message displayed in the progress page.
 *   // The following keys allow for multi-step operations :
 *   // 'sandbox' (read / write): An array that can be freely used to
 *   //   store persistent data between iterations. It is recommended to
 *   //   use this instead of $_SESSION, which is unsafe if the user
 *   //   continues browsing in a separate window while the batch is processing.
 *   // 'finished' (write): A float number between 0 and 1 informing
 *   //   the processing engine of the completion level for the operation.
 *   //   1 (or no value explicitly set) means the operation is finished
 *   //   and the batch processing can continue to the next operation.
 *
 *   $nodes = entity_load_multiple_by_properties('node', array('uid' => $uid, 'type' => $type));
 *   $node = reset($nodes);
 *   $context['results'][] = $node->id() . ' : ' . SafeMarkup::checkPlain($node->label());
 *   $context['message'] = SafeMarkup::checkPlain($node->label());
 * }
 *
 * // A more advanced example is a multi-step operation that loads all rows,
 * // five by five.
 * function my_function_2(&$context) {
 *   if (empty($context['sandbox'])) {
 *     $context['sandbox']['progress'] = 0;
 *     $context['sandbox']['current_id'] = 0;
 *     $context['sandbox']['max'] = db_query('SELECT COUNT(DISTINCT id) FROM {example}')->fetchField();
 *   }
 *   $limit = 5;
 *   $result = db_select('example')
 *     ->fields('example', array('id'))
 *     ->condition('id', $context['sandbox']['current_id'], '>')
 *     ->orderBy('id')
 *     ->range(0, $limit)
 *     ->execute();
 *   foreach ($result as $row) {
 *     $context['results'][] = $row->id . ' : ' . SafeMarkup::checkPlain($row->title);
 *     $context['sandbox']['progress']++;
 *     $context['sandbox']['current_id'] = $row->id;
 *     $context['message'] = SafeMarkup::checkPlain($row->title);
 *   }
 *   if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
 *     $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
 *   }
 * }
 * @endcode
 *
 * Sample callback_batch_finished():
 * @code
 * function my_finished_callback($success, $results, $operations) {
 *   // The 'success' parameter means no fatal PHP errors were detected. All
 *   // other error management should be handled using 'results'.
 *   if ($success) {
 *     $message = \Drupal::translation()->formatPlural(count($results), 'One post processed.', '@count posts processed.');
 *   }
 *   else {
 *     $message = t('Finished with an error.');
 *   }
 *   drupal_set_message($message);
 *   // Providing data for the redirected page is done through $_SESSION.
 *   foreach ($results as $result) {
 *     $items[] = t('Loaded node %title.', array('%title' => $result));
 *   }
 *   $_SESSION['my_batch_results'] = $items;
 * }
 * @endcode
 */

/**
 * Adds a new batch.
 *
 * Batch operations are added as new batch sets. Batch sets are used to spread
 * processing (primarily, but not exclusively, forms processing) over several
 * page requests. This helps to ensure that the processing is not interrupted
 * due to PHP timeouts, while users are still able to receive feedback on the
 * progress of the ongoing operations. Combining related operations into
 * distinct batch sets provides clean code independence for each batch set,
 * ensuring that two or more batches, submitted independently, can be processed
 * without mutual interference. Each batch set may specify its own set of
 * operations and results, produce its own UI messages, and trigger its own
 * 'finished' callback. Batch sets are processed sequentially, with the progress
 * bar starting afresh for each new set.
 *
 * @param $batch_definition
 *   An associative array defining the batch, with the following elements (all
 *   are optional except as noted):
 *   - operations: (required) Array of operations to be performed, where each
 *     item is an array consisting of the name of an implementation of
 *     callback_batch_operation() and an array of parameter.
 *     Example:
 *     @code
 *     array(
 *       array('callback_batch_operation_1', array($arg1)),
 *       array('callback_batch_operation_2', array($arg2_1, $arg2_2)),
 *     )
 *     @endcode
 *   - title: A safe, translated string to use as the title for the progress
 *     page. Defaults to t('Processing').
 *   - init_message: Message displayed while the processing is initialized.
 *     Defaults to t('Initializing.').
 *   - progress_message: Message displayed while processing the batch. Available
 *     placeholders are @current, @remaining, @total, @percentage, @estimate and
 *     @elapsed. Defaults to t('Completed @current of @total.').
 *   - error_message: Message displayed if an error occurred while processing
 *     the batch. Defaults to t('An error has occurred.').
 *   - finished: Name of an implementation of callback_batch_finished(). This is
 *     executed after the batch has completed. This should be used to perform
 *     any result massaging that may be needed, and possibly save data in
 *     $_SESSION for display after final page redirection.
 *   - file: Path to the file containing the definitions of the 'operations' and
 *     'finished' functions, for instance if they don't reside in the main
 *     .module file. The path should be relative to base_path(), and thus should
 *     be built using drupal_get_path().
 *   - css: Array of paths to CSS files to be used on the progress page.
 *   - url_options: options passed to url() when constructing redirect URLs for
 *     the batch.
 *   - safe_strings: Internal use only. Used to store and retrieve strings
 *     marked as safe between requests.
 *   - progressive: A Boolean that indicates whether or not the batch needs to
 *     run progressively. TRUE indicates that the batch will run in more than
 *     one run. FALSE (default) indicates that the batch will finish in a single
 *     run.
 *   - queue: An override of the default queue (with name and class fields
 *     optional). An array containing two elements:
 *     - name: Unique identifier for the queue.
 *     - class: The name of a class that implements
 *       \Drupal\Core\Queue\QueueInterface, including the full namespace but not
 *       starting with a backslash. It must have a constructor with two
 *       arguments: $name and a \Drupal\Core\Database\Connection object.
 *       Typically, the class will either be \Drupal\Core\Queue\Batch or
 *       \Drupal\Core\Queue\BatchMemory. Defaults to Batch if progressive is
 *       TRUE, or to BatchMemory if progressive is FALSE.
 */
function batch_set($batch_definition) {
  if ($batch_definition) {
    $batch =& batch_get();

    // Initialize the batch if needed.
    if (empty($batch)) {
      $batch = array(
        'sets' => array(),
        'has_form_submits' => FALSE,
      );
    }

    // Base and default properties for the batch set.
    $init = array(
      'sandbox' => array(),
      'results' => array(),
      'success' => FALSE,
      'start' => 0,
      'elapsed' => 0,
    );
    $defaults = array(
      'title' => t('Processing'),
      'init_message' => t('Initializing.'),
      'progress_message' => t('Completed @current of @total.'),
      'error_message' => t('An error has occurred.'),
      'css' => array(),
    );
    $batch_set = $init + $batch_definition + $defaults;

    // Tweak init_message to avoid the bottom of the page flickering down after
    // init phase.
    $batch_set['init_message'] .= '<br/>&nbsp;';

    // The non-concurrent workflow of batch execution allows us to save
    // numberOfItems() queries by handling our own counter.
    $batch_set['total'] = count($batch_set['operations']);
    $batch_set['count'] = $batch_set['total'];

    // Add the set to the batch.
    if (empty($batch['id'])) {
      // The batch is not running yet. Simply add the new set.
      $batch['sets'][] = $batch_set;
    }
    else {
      // The set is being added while the batch is running. Insert the new set
      // right after the current one to ensure execution order, and store its
      // operations in a queue.
      $index = $batch['current_set'] + 1;
      $slice1 = array_slice($batch['sets'], 0, $index);
      $slice2 = array_slice($batch['sets'], $index);
      $batch['sets'] = array_merge($slice1, array($batch_set), $slice2);
      _batch_populate_queue($batch, $index);
    }
  }
}

/**
 * Processes the batch.
 *
 * This function is generally not needed in form submit handlers;
 * Form API takes care of batches that were set during form submission.
 *
 * @param \Drupal\Core\Url|string $redirect
 *   (optional) Either path or Url object to redirect to when the batch has
 *   finished processing.
 * @param \Drupal\Core\Url $url
 *   (optional - should only be used for separate scripts like update.php)
 *   URL of the batch processing page.
 * @param $redirect_callback
 *   (optional) Specify a function to be called to redirect to the progressive
 *   processing page.
 *
 * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
 *   A redirect response if the batch is progressive. No return value otherwise.
 */
function batch_process($redirect = NULL, Url $url = NULL, $redirect_callback = NULL) {
  $batch =& batch_get();

  if (isset($batch)) {
    // Add process information
    $process_info = array(
      'current_set' => 0,
      'progressive' => TRUE,
      'url' => isset($url) ? $url : Url::fromRoute('system.batch_page.html'),
      'source_url' => Url::fromRouteMatch(\Drupal::routeMatch()),
      'batch_redirect' => $redirect,
      'theme' => \Drupal::theme()->getActiveTheme()->getName(),
      'redirect_callback' => $redirect_callback,
    );
    $batch += $process_info;

    // The batch is now completely built. Allow other modules to make changes
    // to the batch so that it is easier to reuse batch processes in other
    // environments.
    \Drupal::moduleHandler()->alter('batch', $batch);

    // Assign an arbitrary id: don't rely on a serial column in the 'batch'
    // table, since non-progressive batches skip database storage completely.
    $batch['id'] = db_next_id();

    // Move operations to a job queue. Non-progressive batches will use a
    // memory-based queue.
    foreach ($batch['sets'] as $key => $batch_set) {
      _batch_populate_queue($batch, $key);
    }

    // Initiate processing.
    if ($batch['progressive']) {
      // Now that we have a batch id, we can generate the redirection link in
      // the generic error message.
      /** @var \Drupal\Core\Url $batch_url */
      $batch_url = $batch['url'];
      /** @var \Drupal\Core\Url $error_url */
      $error_url = clone $batch_url;
      $query_options = $error_url->getOption('query');
      $query_options['id'] = $batch['id'];
      $query_options['op'] = 'finished';
      $error_url->setOption('query', $query_options);

      $batch['error_message'] = t('Please continue to <a href="@error_url">the error page</a>', array('@error_url' => $error_url->toString(TRUE)->getGeneratedUrl()));

      // Clear the way for the redirection to the batch processing page, by
      // saving and unsetting the 'destination', if there is any.
      $request = \Drupal::request();
      if ($request->query->has('destination')) {
        $batch['destination'] = $request->query->get('destination');
        $request->query->remove('destination');
      }

      // Store safe strings.
      // @todo Ensure we are not storing an excessively large string list in:
      //   https://www.drupal.org/node/2295823
      $batch['safe_strings'] = SafeMarkup::getAll();

      // Store the batch.
      \Drupal::service('batch.storage')->create($batch);

      // Set the batch number in the session to guarantee that it will stay alive.
      $_SESSION['batches'][$batch['id']] = TRUE;

      // Redirect for processing.
      $query_options = $error_url->getOption('query');
      $query_options['op'] = 'start';
      $query_options['id'] = $batch['id'];
      $batch_url->setOption('query', $query_options);
      if (($function = $batch['redirect_callback']) && function_exists($function)) {
        $function($batch_url->toString(), ['query' => $query_options]);
      }
      else {
        return new RedirectResponse($batch_url->setAbsolute()->toString(TRUE)->getGeneratedUrl());
      }
    }
    else {
      // Non-progressive execution: bypass the whole progressbar workflow
      // and execute the batch in one pass.
      require_once __DIR__ . '/batch.inc';
      _batch_process();
    }
  }
}

/**
 * Retrieves the current batch.
 */
function &batch_get() {
  // Not drupal_static(), because Batch API operates at a lower level than most
  // use-cases for resetting static variables, and we specifically do not want a
  // global drupal_static_reset() resetting the batch information. Functions
  // that are part of the Batch API and need to reset the batch information may
  // call batch_get() and manipulate the result by reference. Functions that are
  // not part of the Batch API can also do this, but shouldn't.
  static $batch = array();
  return $batch;
}

/**
 * Populates a job queue with the operations of a batch set.
 *
 * Depending on whether the batch is progressive or not, the
 * Drupal\Core\Queue\Batch or Drupal\Core\Queue\BatchMemory handler classes will
 * be used.
 *
 * @param $batch
 *   The batch array.
 * @param $set_id
 *   The id of the set to process.
 *
 * @return
 *   The name and class of the queue are added by reference to the batch set.
 */
function _batch_populate_queue(&$batch, $set_id) {
  $batch_set = &$batch['sets'][$set_id];

  if (isset($batch_set['operations'])) {
    $batch_set += array(
      'queue' => array(
        'name' => 'drupal_batch:' . $batch['id'] . ':' . $set_id,
        'class' => $batch['progressive'] ? 'Drupal\Core\Queue\Batch' : 'Drupal\Core\Queue\BatchMemory',
      ),
    );

    $queue = _batch_queue($batch_set);
    $queue->createQueue();
    foreach ($batch_set['operations'] as $operation) {
      $queue->createItem($operation);
    }

    unset($batch_set['operations']);
  }
}

/**
 * Returns a queue object for a batch set.
 *
 * @param $batch_set
 *   The batch set.
 *
 * @return
 *   The queue object.
 */
function _batch_queue($batch_set) {
  static $queues;

  if (!isset($queues)) {
    $queues = array();
  }

  if (isset($batch_set['queue'])) {
    $name = $batch_set['queue']['name'];
    $class = $batch_set['queue']['class'];

    if (!isset($queues[$class][$name])) {
      $queues[$class][$name] = new $class($name, \Drupal::database());
    }
    return $queues[$class][$name];
  }
}

/**
 * @} End of "defgroup batch".
 */
