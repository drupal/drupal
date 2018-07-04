<?php

namespace Drupal\Core\Render\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a machine name render element.
 *
 * Provides a form element to enter a machine name, which is validated to ensure
 * that the name is unique and does not contain disallowed characters.
 *
 * The element may be automatically populated via JavaScript when used in
 * conjunction with a separate "source" form element (typically specifying the
 * human-readable name). As the user types text into the source element, the
 * JavaScript converts all values to lower case, replaces any remaining
 * disallowed characters with a replacement, and populates the associated
 * machine name form element.
 *
 * Properties:
 * - #machine_name: An associative array containing:
 *   - exists: A callable to invoke for checking whether a submitted machine
 *     name value already exists. The arguments passed to the callback will be:
 *     - The submitted value.
 *     - The element array.
 *     - The form state object.
 *     In most cases, an existing API or menu argument loader function can be
 *     re-used. The callback is only invoked if the submitted value differs from
 *     the element's initial #default_value. The initial #default_value is
 *     stored in form state so AJAX forms can be reliably validated.
 *   - source: (optional) The #array_parents of the form element containing the
 *     human-readable name (i.e., as contained in the $form structure) to use as
 *     source for the machine name. Defaults to array('label').
 *   - label: (optional) Text to display as label for the machine name value
 *     after the human-readable name form element. Defaults to t('Machine name').
 *   - replace_pattern: (optional) A regular expression (without delimiters)
 *     matching disallowed characters in the machine name. Defaults to
 *     '[^a-z0-9_]+'.
 *   - replace: (optional) A character to replace disallowed characters in the
 *     machine name via JavaScript. Defaults to '_' (underscore). When using a
 *     different character, 'replace_pattern' needs to be set accordingly.
 *   - error: (optional) A custom form error message string to show, if the
 *     machine name contains disallowed characters.
 *   - standalone: (optional) Whether the live preview should stay in its own
 *     form element rather than in the suffix of the source element. Defaults
 *     to FALSE.
 * - #maxlength: (optional) Maximum allowed length of the machine name. Defaults
 *   to 64.
 * - #disabled: (optional) Should be set to TRUE if an existing machine name
 *   must not be changed after initial creation.
 *
 * Usage example:
 * @code
 * $form['id'] = array(
 *   '#type' => 'machine_name',
 *   '#default_value' => $this->entity->id(),
 *   '#disabled' => !$this->entity->isNew(),
 *   '#maxlength' => 64,
 *   '#description' => $this->t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.'),
 *   '#machine_name' => array(
 *     'exists' => array($this, 'exists'),
 *   ),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textfield
 *
 * @FormElement("machine_name")
 */
class MachineName extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#default_value' => NULL,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 60,
      '#autocomplete_route_name' => FALSE,
      '#process' => [
        [$class, 'processMachineName'],
        [$class, 'processAutocomplete'],
        [$class, 'processAjaxForm'],
      ],
      '#element_validate' => [
        [$class, 'validateMachineName'],
      ],
      '#pre_render' => [
        [$class, 'preRenderTextfield'],
      ],
      '#theme' => 'input__textfield',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      return is_scalar($input) ? (string) $input : '';
    }
    return NULL;
  }

  /**
   * Processes a machine-readable name form element.
   *
   * @param array $element
   *   The form element to process. See main class documentation for properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processMachineName(&$element, FormStateInterface $form_state, &$complete_form) {
    // We need to pass the langcode to the client.
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Apply default form element properties.
    $element += [
      '#title' => t('Machine-readable name'),
      '#description' => t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#machine_name' => [],
      '#field_prefix' => '',
      '#field_suffix' => '',
      '#suffix' => '',
    ];
    // A form element that only wants to set one #machine_name property (usually
    // 'source' only) would leave all other properties undefined, if the defaults
    // were defined by an element plugin. Therefore, we apply the defaults here.
    $element['#machine_name'] += [
      'source' => ['label'],
      'target' => '#' . $element['#id'],
      'label' => t('Machine name'),
      'replace_pattern' => '[^a-z0-9_]+',
      'replace' => '_',
      'standalone' => FALSE,
      'field_prefix' => $element['#field_prefix'],
      'field_suffix' => $element['#field_suffix'],
    ];

    // Store the initial value in form state. The machine name needs this to
    // ensure that the exists function is not called for existing values when
    // editing them.
    $initial_values = $form_state->get('machine_name.initial_values') ?: [];
    // Store the initial values in an array so we can differentiate between a
    // NULL default value and a new machine name element.
    if (!array_key_exists($element['#name'], $initial_values)) {
      $initial_values[$element['#name']] = $element['#default_value'];
      $form_state->set('machine_name.initial_values', $initial_values);
    }

    // By default, machine names are restricted to Latin alphanumeric characters.
    // So, default to LTR directionality.
    if (!isset($element['#attributes'])) {
      $element['#attributes'] = [];
    }
    $element['#attributes'] += ['dir' => LanguageInterface::DIRECTION_LTR];

    // The source element defaults to array('name'), but may have been overridden.
    if (empty($element['#machine_name']['source'])) {
      return $element;
    }

    // Retrieve the form element containing the human-readable name from the
    // complete form in $form_state. By reference, because we may need to append
    // a #field_suffix that will hold the live preview.
    $key_exists = NULL;
    $source = NestedArray::getValue($form_state->getCompleteForm(), $element['#machine_name']['source'], $key_exists);
    if (!$key_exists) {
      return $element;
    }

    $suffix_id = $source['#id'] . '-machine-name-suffix';
    $element['#machine_name']['suffix'] = '#' . $suffix_id;

    if ($element['#machine_name']['standalone']) {
      $element['#suffix'] = $element['#suffix'] . ' <small id="' . $suffix_id . '">&nbsp;</small>';
    }
    else {
      // Append a field suffix to the source form element, which will contain
      // the live preview of the machine name.
      $source += ['#field_suffix' => ''];
      $source['#field_suffix'] = $source['#field_suffix'] . ' <small id="' . $suffix_id . '">&nbsp;</small>';

      $parents = array_merge($element['#machine_name']['source'], ['#field_suffix']);
      NestedArray::setValue($form_state->getCompleteForm(), $parents, $source['#field_suffix']);
    }

    $element['#attached']['library'][] = 'core/drupal.machine-name';
    $options = [
      'replace_pattern',
      'replace_token',
      'replace',
      'maxlength',
      'target',
      'label',
      'field_prefix',
      'field_suffix',
      'suffix',
    ];

    /** @var \Drupal\Core\Access\CsrfTokenGenerator $token_generator */
    $token_generator = \Drupal::service('csrf_token');
    $element['#machine_name']['replace_token'] = $token_generator->get($element['#machine_name']['replace_pattern']);

    $element['#attached']['drupalSettings']['machineName']['#' . $source['#id']] = array_intersect_key($element['#machine_name'], array_flip($options));
    $element['#attached']['drupalSettings']['langcode'] = $language->getId();

    return $element;
  }

  /**
   * Form element validation handler for machine_name elements.
   *
   * Note that #maxlength is validated by _form_validate() already.
   *
   * This checks that the submitted value:
   * - Does not contain the replacement character only.
   * - Does not contain disallowed characters.
   * - Is unique; i.e., does not already exist.
   * - Does not exceed the maximum length (via #maxlength).
   * - Cannot be changed after creation (via #disabled).
   */
  public static function validateMachineName(&$element, FormStateInterface $form_state, &$complete_form) {
    // Verify that the machine name not only consists of replacement tokens.
    if (preg_match('@^' . $element['#machine_name']['replace'] . '+$@', $element['#value'])) {
      $form_state->setError($element, t('The machine-readable name must contain unique characters.'));
    }

    // Verify that the machine name contains no disallowed characters.
    if (preg_match('@' . $element['#machine_name']['replace_pattern'] . '@', $element['#value'])) {
      if (!isset($element['#machine_name']['error'])) {
        // Since a hyphen is the most common alternative replacement character,
        // a corresponding validation error message is supported here.
        if ($element['#machine_name']['replace'] == '-') {
          $form_state->setError($element, t('The machine-readable name must contain only lowercase letters, numbers, and hyphens.'));
        }
        // Otherwise, we assume the default (underscore).
        else {
          $form_state->setError($element, t('The machine-readable name must contain only lowercase letters, numbers, and underscores.'));
        }
      }
      else {
        $form_state->setError($element, $element['#machine_name']['error']);
      }
    }

    // Verify that the machine name is unique. If the value matches the initial
    // default value then it does not need to be validated as the machine name
    // element assumes the form is editing the existing value.
    $initial_values = $form_state->get('machine_name.initial_values') ?: [];
    if (!array_key_exists($element['#name'], $initial_values) || $initial_values[$element['#name']] !== $element['#value']) {
      $function = $element['#machine_name']['exists'];
      if (call_user_func($function, $element['#value'], $element, $form_state)) {
        $form_state->setError($element, t('The machine-readable name is already in use. It must be unique.'));
      }
    }
  }

}
