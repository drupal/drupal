<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormState.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores information about the state of a form.
 *
 * @todo Remove usage of \ArrayAccess in https://www.drupal.org/node/2310255.
 */
class FormState implements FormStateInterface, \ArrayAccess {

  /**
   * Tracks if any errors have been set on any form.
   *
   * @var bool
   */
  protected static $anyErrors = FALSE;

  /**
   * The internal storage of the form state.
   *
   * @var array
   */
  protected $internalStorage = array();

  /**
   * The complete form structure.
   *
   * #process, #after_build, #element_validate, and other handlers being invoked
   * on a form element may use this reference to access other information in the
   * form the element is contained in.
   *
   * @see self::getCompleteForm()
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $complete_form;

  /**
   * An associative array of information stored by Form API that is necessary to
   * build and rebuild the form from cache when the original context may no
   * longer be available:
   *   - callback: The actual callback to be used to retrieve the form array.
   *     Can be any callable. If none is provided $form_id is used as the name
   *     of a function to call instead.
   *   - args: A list of arguments to pass to the form constructor.
   *   - files: An optional array defining include files that need to be loaded
   *     for building the form. Each array entry may be the path to a file or
   *     another array containing values for the parameters 'type', 'module' and
   *     'name' as needed by module_load_include(). The files listed here are
   *     automatically loaded by form_get_cache(). By default the current menu
   *     router item's 'file' definition is added, if any. Use
   *     form_load_include() to add include files from a form constructor.
   *   - form_id: Identification of the primary form being constructed and
   *     processed.
   *   - base_form_id: Identification for a base form, as declared in the form
   *     class's \Drupal\Core\Form\BaseFormIdInterface::getBaseFormId() method.
   *
   * @var array
   */
  protected $build_info = array(
    'args' => array(),
    'files' => array(),
  );

  /**
   * Similar to self::$build_info, but pertaining to
   * \Drupal\Core\Form\FormBuilderInterface::rebuildForm().
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $rebuild_info = array();

  /**
   * Normally, after the entire form processing is completed and submit handlers
   * have run, a form is considered to be done and
   * \Drupal\Core\Form\FormSubmitterInterface::redirectForm() will redirect the
   * user to a new page using a GET request (so a browser refresh does not
   * re-submit the form). However, if 'rebuild' has been set to TRUE, then a new
   * copy of the form is immediately built and sent to the browser, instead of a
   * redirect. This is used for multi-step forms, such as wizards and
   * confirmation forms. Normally, $form_state['rebuild'] is set by a submit
   * handler, since its is usually logic within a submit handler that determines
   * whether a form is done or requires another step. However, a validation
   * handler may already set $form_state['rebuild'] to cause the form processing
   * to bypass submit handlers and rebuild the form instead, even if there are
   * no validation errors.
   *
   * This property is uncacheable.
   *
   * @see self::setRebuild()
   *
   * @var bool
   */
  protected $rebuild = FALSE;

  /**
   * Used when a form needs to return some kind of a
   * \Symfony\Component\HttpFoundation\Response object, e.g., a
   * \Symfony\Component\HttpFoundation\BinaryFileResponse when triggering a
   * file download. If you use the $form_state['redirect'] key, it will be used
   * to build a \Symfony\Component\HttpFoundation\RedirectResponse and will
   * populate this key.
   *
   * @var \Symfony\Component\HttpFoundation\Response|null
   */
  protected $response;

  /**
   * Used to redirect the form on submission. It may either be a  string
   * containing the destination URL, or an array of arguments compatible with
   * url(). See url() for complete information.
   *
   * This property is uncacheable.
   *
   * @var string|array|null
   */
  protected $redirect;

  /**
   * Used for route-based redirects.
   *
   * This property is uncacheable.
   *
   * @var \Drupal\Core\Url|array
   */
  protected $redirect_route;

  /**
   * If set to TRUE the form will NOT perform a redirect, even if
   * self::$redirect is set.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $no_redirect;

  /**
   * The HTTP form method to use for finding the input for this form.
   *
   * May be 'post' or 'get'. Defaults to 'post'. Note that 'get' method forms do
   * not use form ids so are always considered to be submitted, which can have
   * unexpected effects. The 'get' method should only be used on forms that do
   * not change data, as that is exclusively the domain of 'post.'
   *
   * This property is uncacheable.
   *
   * @var string
   */
  protected $method = 'post';

  /**
   * If set to TRUE the original, unprocessed form structure will be cached,
   * which allows the entire form to be rebuilt from cache. A typical form
   * workflow involves two page requests; first, a form is built and rendered
   * for the user to fill in. Then, the user fills the form in and submits it,
   * triggering a second page request in which the form must be built and
   * processed. By default, $form and $form_state are built from scratch during
   * each of these page requests. Often, it is necessary or desired to persist
   * the $form and $form_state variables from the initial page request to the
   * one that processes the submission. 'cache' can be set to TRUE to do this.
   * A prominent example is an Ajax-enabled form, in which ajax_process_form()
   * enables form caching for all forms that include an element with the #ajax
   * property. (The Ajax handler has no way to build the form itself, so must
   * rely on the cached version.) Note that the persistence of $form and
   * $form_state happens automatically for (multi-step) forms having the
   * self::$rebuild flag set, regardless of the value for self::$cache.
   *
   * @var bool
   */
  protected $cache = FALSE;

  /**
   * If set to TRUE the form will NOT be cached, even if 'cache' is set.
   *
   * @var bool
   */
  protected $no_cache;

  /**
   * An associative array of values submitted to the form.
   *
   * The validation functions and submit functions use this array for nearly all
   * their decision making. (Note that #tree determines whether the values are a
   * flat array or an array whose structure parallels the $form array. See the
   * @link forms_api_reference.html Form API reference @endlink for more
   * information.)
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $values;

  /**
   * The array of values as they were submitted by the user.
   *
   * These are raw and unvalidated, so should not be used without a thorough
   * understanding of security implications. In almost all cases, code should
   * use the data in the 'values' array exclusively. The most common use of this
   * key is for multi-step forms that need to clear some of the user input when
   * setting 'rebuild'. The values correspond to \Drupal::request()->request or
   * \Drupal::request()->query, depending on the 'method' chosen.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $input;

  /**
   * If TRUE and the method is GET, a form_id is not necessary.
   *
   * This should only be used on RESTful GET forms that do NOT write data, as
   * this could lead to security issues. It is useful so that searches do not
   * need to have a form_id in their query arguments to trigger the search.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $always_process;

  /**
   * Ordinarily, a form is only validated once, but there are times when a form
   * is resubmitted internally and should be validated again. Setting this to
   * TRUE will force that to happen. This is most likely to occur during Ajax
   * operations.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $must_validate;

  /**
   * If TRUE, the form was submitted programmatically, usually invoked via
   * \Drupal\Core\Form\FormBuilderInterface::submitForm(). Defaults to FALSE.
   *
   * @var bool
   */
  protected $programmed = FALSE;

  /**
   * If TRUE, programmatic form submissions are processed without taking #access
   * into account. Set this to FALSE when submitting a form programmatically
   * with values that may have been input by the user executing the current
   * request; this will cause #access to be respected as it would on a normal
   * form submission. Defaults to TRUE.
   *
   * @var bool
   */
  protected $programmed_bypass_access_check = TRUE;

  /**
   * TRUE signifies correct form submission. This is always TRUE for programmed
   * forms coming from \Drupal\Core\Form\FormBuilderInterface::submitForm() (see
   * 'programmed' key), or if the form_id coming from the
   * \Drupal::request()->request data is set and matches the current form_id.
   *
   * @var bool
   */
  protected $process_input;

  /**
   * If TRUE, the form has been submitted. Defaults to FALSE.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $submitted = FALSE;

  /**
   * If TRUE, the form was submitted and has been processed and executed.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $executed = FALSE;

  /**
   * The form element that triggered submission, which may or may not be a
   * button (in the case of Ajax forms). This key is often used to distinguish
   * between various buttons in a submit handler, and is also used in Ajax
   * handlers.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  protected $triggering_element;

  /**
   * If TRUE, there is a file element and Form API will set the appropriate
   * 'enctype' HTML attribute on the form.
   *
   * @var bool
   */
  protected $has_file_element;

  /**
   * Contains references to details elements to render them within vertical tabs.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $groups = array();

  /**
   *  This is not a special key, and no specific support is provided for it in
   *  the Form API. By tradition it was the location where application-specific
   *  data was stored for communication between the submit, validation, and form
   *  builder functions, especially in a multi-step-style form. Form
   *  implementations may use any key(s) within $form_state (other than the keys
   *  listed here and other reserved ones used by Form API internals) for this
   *  kind of storage. The recommended way to ensure that the chosen key doesn't
   *  conflict with ones used by the Form API or other modules is to use the
   *  module name as the key name or a prefix for the key name. For example, the
   *  entity form classes use $this->entity in entity forms, or
   *  $form_state['controller']->getEntity() outside the controller, to store
   *  information about the entity being edited, and this information stays
   *  available across successive clicks of the "Preview" button (if available)
   *  as well as when the "Save" button is finally clicked.
   *
   * @var array
   */
  protected $storage = array();

  /**
   * A list containing copies of all submit and button elements in the form.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $buttons = array();

  /**
   * Holds temporary data accessible during the current page request only.
   *
   * All $form_state properties that are not reserved keys (see
   * other properties marked as uncacheable) persist throughout a multistep form
   * sequence. Form API provides this key for modules to communicate information
   * across form-related functions during a single page request. It may be used
   * to temporarily save data that does not need to or should not be cached
   * during the whole form workflow; e.g., data that needs to be accessed during
   * the current form build process only. There is no use-case for this
   * functionality in Drupal core.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $temporary;

  /**
   * Tracks if the form has finished validation.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  protected $validation_complete = FALSE;

  /**
   * Contains errors for this form.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * Stores which errors should be limited during validation.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  protected $limit_validation_errors;

  /**
   * Stores the gathered validation handlers.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  protected $validate_handlers;

  /**
   * Stores the gathered submission handlers.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  protected $submit_handlers;

  /**
   * Constructs a \Drupal\Core\Form\FormState object.
   *
   * @param array $form_state_additions
   *   (optional) An associative array used to build the current state of the
   *   form. Use this to pass additional information to the form, such as the
   *   langcode. Defaults to an empty array.
   */
  public function __construct(array $form_state_additions = array()) {
    $this->setFormState($form_state_additions);
  }

  /**
   * {@inheritdoc}
   */
  public function setFormState(array $form_state_additions) {
    foreach ($form_state_additions as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableArray($allowed_keys = array()) {
    $cacheable_array = array(
      'build_info' => $this->build_info,
      'response' => $this->response,
      'cache' => $this->cache,
      'no_cache' => $this->no_cache,
      'programmed' => $this->programmed,
      'programmed_bypass_access_check' => $this->programmed_bypass_access_check,
      'process_input' => $this->process_input,
      'has_file_element' => $this->has_file_element,
      'storage' => $this->storage,
    ) + $this->internalStorage;
    foreach ($allowed_keys as $allowed_key) {
      $cacheable_array[$allowed_key] = $this->get($allowed_key);
    }
    return $cacheable_array;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleteForm(array &$complete_form) {
    $this->complete_form = &$complete_form;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getCompleteForm() {
    return $this->complete_form;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->{$offset}) || isset($this->internalStorage[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function &offsetGet($offset) {
    $value = &$this->get($offset);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    $this->set($offset, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    if (property_exists($this, $offset)) {
      $this->{$offset} = NULL;
    }
    unset($this->internalStorage[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($property, $value) {
    if (!$this->has($property)) {
      $this->set($property, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &get($property) {
    if (property_exists($this, $property)) {
      return $this->{$property};
    }
    else {
      if (!isset($this->internalStorage[$property])) {
        $this->internalStorage[$property] = NULL;
      }
      return $this->internalStorage[$property];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->internalStorage[$property] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function has($property) {
    if (property_exists($this, $property)) {
      return $this->{$property} !== NULL;
    }

    return array_key_exists($property, $this->internalStorage);
  }

  /**
   * {@inheritdoc}
   */
  public function addBuildInfo($property, $value) {
    $build_info = $this->get('build_info');
    $build_info[$property] = $value;
    $this->set('build_info', $build_info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values ?: array();
  }

  /**
   * {@inheritdoc}
   */
  public function addValue($property, $value) {
    $values = $this->getValues();
    $values[$property] = $value;
    $this->set('values', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValueForElement($element, $value) {
    $values = $this->getValues();
    NestedArray::setValue($values, $element['#parents'], $value, TRUE);
    $this->set('values', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponse(Response $response) {
    $this->set('response', $response);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirect(Url $url) {
    $this->set('redirect_route', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    // Skip redirection for form submissions invoked via
    // \Drupal\Core\Form\FormBuilderInterface::submitForm().
    if ($this->get('programmed')) {
      return FALSE;
    }
    // Skip redirection if rebuild is activated.
    if ($this->get('rebuild')) {
      return FALSE;
    }
    // Skip redirection if it was explicitly disallowed.
    if ($this->get('no_redirect')) {
      return FALSE;
    }

    // Check for a route-based redirection.
    if ($redirect_route = $this->get('redirect_route')) {
      // @todo Remove once all redirects are converted to \Drupal\Core\Url. See
      //   https://www.drupal.org/node/2189661.
      if (!($redirect_route instanceof Url)) {
        $redirect_route += array(
          'route_parameters' => array(),
          'options' => array(),
        );
        $redirect_route = new Url($redirect_route['route_name'], $redirect_route['route_parameters'], $redirect_route['options']);
      }

      $redirect_route->setAbsolute();
      return $redirect_route;
    }

    return $this->get('redirect');
  }

  /**
   * Sets the global status of errors.
   *
   * @param bool $errors
   *   TRUE if any form has any errors, FALSE otherwise.
   */
  protected static function setAnyErrors($errors = TRUE) {
    static::$anyErrors = $errors;
  }

  /**
   * {@inheritdoc}
   */
  public static function hasAnyErrors() {
    return static::$anyErrors;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorByName($name, $message = '') {
    if ($this->get('validation_complete')) {
      throw new \LogicException('Form errors cannot be set after form validation has finished.');
    }

    $errors = $this->getErrors();
    if (!isset($errors[$name])) {
      $record = TRUE;
      $limit_validation_errors = $this->get('limit_validation_errors');
      if ($limit_validation_errors !== NULL) {
        // #limit_validation_errors is an array of "sections" within which user
        // input must be valid. If the element is within one of these sections,
        // the error must be recorded. Otherwise, it can be suppressed.
        // #limit_validation_errors can be an empty array, in which case all
        // errors are suppressed. For example, a "Previous" button might want
        // its submit action to be triggered even if none of the submitted
        // values are valid.
        $record = FALSE;
        foreach ($limit_validation_errors as $section) {
          // Exploding by '][' reconstructs the element's #parents. If the
          // reconstructed #parents begin with the same keys as the specified
          // section, then the element's values are within the part of
          // $form_state['values'] that the clicked button requires to be valid,
          // so errors for this element must be recorded. As the exploded array
          // will all be strings, we need to cast every value of the section
          // array to string.
          if (array_slice(explode('][', $name), 0, count($section)) === array_map('strval', $section)) {
            $record = TRUE;
            break;
          }
        }
      }
      if ($record) {
        $errors[$name] = $message;
        $this->set('errors', $errors);
        static::setAnyErrors();
        if ($message) {
          $this->drupalSetMessage($message, 'error');
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setError(&$element, $message = '') {
    $this->setErrorByName(implode('][', $element['#parents']), $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrors() {
    $this->set('errors', array());
    static::setAnyErrors(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getError($element) {
    if ($errors = $this->getErrors($this)) {
      $parents = array();
      foreach ($element['#parents'] as $parent) {
        $parents[] = $parent;
        $key = implode('][', $parents);
        if (isset($errors[$key])) {
          return $errors[$key];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->get('errors');
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuild($rebuild = TRUE) {
    $this->set('rebuild', $rebuild);
    return $this;
  }

  /**
   * Wraps drupal_set_message().
   *
   * @return array|null
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    return drupal_set_message($message, $type, $repeat);
  }

}
