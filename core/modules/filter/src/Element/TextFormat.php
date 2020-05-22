<?php

namespace Drupal\filter\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides a text format render element.
 *
 * Properties:
 * - #base_type: The form element #type to use for the 'value' element.
 *   'textarea' by default.
 * - #format: (optional) The text format ID to preselect. If omitted, the
 *   default format for the current user will be used.
 * - #allowed_formats: (optional) An array of text format IDs that are available
 *   for this element. If omitted, all text formats that the current user has
 *   access to will be allowed.
 *
 * Usage Example:
 * @code
 * $form['body'] = array(
 *   '#type' => 'text_format',
 *   '#title' => 'Body',
 *   '#format' => 'full_html',
 *   '#default_value' => '<p>The quick brown fox jumped over the lazy dog.</p>',
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Textarea
 *
 * @RenderElement("text_format")
 */
class TextFormat extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processFormat'],
      ],
      '#base_type' => 'textarea',
      '#theme_wrappers' => ['text_format_wrapper'],
    ];
  }

  /**
   * Expands an element into a base element with text format selector attached.
   *
   * The form element will be expanded into two separate form elements, one
   * holding the original element, and the other holding the text format
   * selector:
   * - value: Holds the original element, having its #type changed to the value
   *   of #base_type or 'textarea' by default.
   * - format: Holds the text format details and the text format selection,
   *   using the text format ID specified in #format or the user's default
   *   format by default, if NULL.
   *
   * The resulting value for the element will be an array holding the value and
   * the format. For example, the value for the body element will be:
   * @code
   *   $values = $form_state->getValue('body');
   *   $values['value'] = 'foo';
   *   $values['format'] = 'foo';
   * @endcode
   *
   * @param array $element
   *   The form element to process. See main class documentation for properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   */
  public static function processFormat(&$element, FormStateInterface $form_state, &$complete_form) {
    $user = static::currentUser();

    // Ensure that children appear as subkeys of this element.
    $element['#tree'] = TRUE;
    $blacklist = [
      // Make \Drupal::formBuilder()->doBuildForm() regenerate child properties.
      '#parents',
      '#id',
      '#name',
      // Do not copy this #process function to prevent
      // \Drupal::formBuilder()->doBuildForm() from recursing infinitely.
      '#process',
      // Ensure #pre_render functions will be run.
      '#pre_render',
      // Description is handled by theme_text_format_wrapper().
      '#description',
      // Ensure proper ordering of children.
      '#weight',
      // Properties already processed for the parent element.
      '#prefix',
      '#suffix',
      '#attached',
      '#processed',
      '#theme_wrappers',
    ];
    // Move this element into sub-element 'value'.
    unset($element['value']);
    foreach (Element::properties($element) as $key) {
      if (!in_array($key, $blacklist)) {
        $element['value'][$key] = $element[$key];
      }
    }

    $element['value']['#type'] = $element['#base_type'];
    $element['value'] += static::elementInfo()->getInfo($element['#base_type']);
    // Make sure the #default_value key is set, so we can use it below.
    $element['value'] += ['#default_value' => ''];

    // Turn original element into a text format wrapper.
    $element['#attached']['library'][] = 'filter/drupal.filter';

    // Setup child container for the text format widget.
    $element['format'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['js-filter-wrapper']],
    ];

    // Get a list of formats that the current user has access to.
    $formats = filter_formats($user);

    // Allow the list of formats to be restricted.
    if (isset($element['#allowed_formats'])) {
      // We do not add the fallback format here to allow the use-case of forcing
      // certain text formats to be used for certain text areas. In case the
      // fallback format is supposed to be allowed as well, it must be added to
      // $element['#allowed_formats'] explicitly.
      $formats = array_intersect_key($formats, array_flip($element['#allowed_formats']));
    }

    if (!isset($element['#format']) && !empty($formats)) {
      // If no text format was selected, use the allowed format with the highest
      // weight. This is equivalent to calling filter_default_format().
      $element['#format'] = reset($formats)->id();
    }

    // If #allowed_formats is set, the list of formats must not be modified in
    // any way. Otherwise, however, if all of the following conditions are true,
    // remove the fallback format from the list of formats:
    // 1. The 'always_show_fallback_choice' filter setting has not been
    //    activated.
    // 2. Multiple text formats are available.
    // 3. The fallback format is not the default format.
    // The 'always_show_fallback_choice' filter setting is a hidden setting that
    // has no UI. It defaults to FALSE.
    $config = static::configFactory()->get('filter.settings');
    if (!isset($element['#allowed_formats']) && !$config->get('always_show_fallback_choice')) {
      $fallback_format = $config->get('fallback_format');
      if ($element['#format'] !== $fallback_format && count($formats) > 1) {
        unset($formats[$fallback_format]);
      }
    }

    // Prepare text format guidelines.
    $element['format']['guidelines'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['js-filter-guidelines']],
      '#weight' => 20,
    ];
    $options = [];
    foreach ($formats as $format) {
      $options[$format->id()] = $format->label();
      $element['format']['guidelines'][$format->id()] = [
        '#theme' => 'filter_guidelines',
        '#format' => $format,
      ];
    }

    $element['format']['format'] = [
      '#type' => 'select',
      '#title' => t('Text format'),
      '#options' => $options,
      '#default_value' => $element['#format'],
      '#access' => count($formats) > 1,
      '#weight' => 10,
      '#attributes' => ['class' => ['js-filter-list']],
      '#parents' => array_merge($element['#parents'], ['format']),
    ];

    $element['format']['help'] = [
      '#type' => 'container',
      'about' => [
        '#type' => 'link',
        '#title' => t('About text formats'),
        '#url' => new Url('filter.tips_all'),
        '#attributes' => ['target' => '_blank'],
      ],
      '#weight' => 0,
    ];

    $all_formats = filter_formats();
    $format_exists = isset($all_formats[$element['#format']]);
    $format_allowed = !isset($element['#allowed_formats']) || in_array($element['#format'], $element['#allowed_formats']);
    $user_has_access = isset($formats[$element['#format']]);
    $user_is_admin = $user->hasPermission('administer filters');

    // If the stored format does not exist or if it is not among the allowed
    // formats for this textarea, administrators have to assign a new format.
    if ((!$format_exists || !$format_allowed) && $user_is_admin) {
      $element['format']['format']['#required'] = TRUE;
      $element['format']['format']['#default_value'] = NULL;
      // Force access to the format selector (it may have been denied above if
      // the user only has access to a single format).
      $element['format']['format']['#access'] = TRUE;
    }
    // Disable this widget, if the user is not allowed to use the stored format,
    // or if the stored format does not exist. The 'administer filters'
    // permission only grants access to the filter administration, not to all
    // formats.
    elseif (!$user_has_access || !$format_exists) {
      // Overload default values into #value to make them unalterable.
      $element['value']['#value'] = $element['value']['#default_value'];
      $element['format']['format']['#value'] = $element['format']['format']['#default_value'];

      // Prepend #pre_render callback to replace field value with user notice
      // prior to rendering.
      $element['value'] += ['#pre_render' => []];
      array_unshift($element['value']['#pre_render'], [static::class, 'accessDeniedCallback']);

      // Cosmetic adjustments.
      if (isset($element['value']['#rows'])) {
        $element['value']['#rows'] = 3;
      }
      $element['value']['#disabled'] = TRUE;
      $element['value']['#resizable'] = 'none';

      // Hide the text format selector and any other child element (such as text
      // field's summary).
      foreach (Element::children($element) as $key) {
        if ($key != 'value') {
          $element[$key]['#access'] = FALSE;
        }
      }
    }

    return $element;
  }

  /**
   * Render API callback: Hides the field value of 'text_format' elements.
   *
   * To not break form processing and previews if a user does not have access to
   * a stored text format, the expanded form elements in
   * \Drupal\filter\Element\TextFormat::processFormat() are forced to take over
   * the stored #default_values for 'value' and 'format'. However, to prevent
   * the unfiltered, original #value from being displayed to the user, we
   * replace it with a friendly notice here.
   *
   * @param array $element
   *   The render array to add the access denied message to.
   *
   * @return array
   *   The updated render array.
   */
  public static function accessDeniedCallback(array $element) {
    $element['#value'] = t('This field has been disabled because you do not have sufficient permissions to edit it.');
    return $element;
  }

  /**
   * Wraps the current user.
   *
   * \Drupal\Core\Session\AccountInterface
   */
  protected static function currentUser() {
    return \Drupal::currentUser();
  }

  /**
   * Wraps the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected static function configFactory() {
    return \Drupal::configFactory();
  }

  /**
   * Wraps the element info service.
   *
   * @return \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected static function elementInfo() {
    return \Drupal::service('element_info');
  }

}
