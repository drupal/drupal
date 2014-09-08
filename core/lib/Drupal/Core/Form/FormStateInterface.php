<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormStateInterface.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides an interface for an object containing the current state of a form.
 *
 * This is passed to all form related code so that the caller can use it to
 * examine what in the form changed when the form submission process is
 * complete. Furthermore, it may be used to store information related to the
 * processed data in the form, which will persist across page requests when the
 * 'cache' or 'rebuild' flag is set. See
 * \Drupal\Core\Form\FormState::$internalStorage for documentation of the
 * available flags.
 *
 * @see \Drupal\Core\Form\FormBuilderInterface
 * @see \Drupal\Core\Form\FormValidatorInterface
 * @see \Drupal\Core\Form\FormSubmitterInterface
 * @ingroup form_api
 */
interface FormStateInterface {

  /**
   * Returns a reference to the complete form array.
   *
   * @return array
   *   The complete form array.
   */
  public function &getCompleteForm();

  /**
   * Stores the complete form array.
   *
   * @param array $complete_form
   *   The complete form array.
   *
   * @return $this
   */
  public function setCompleteForm(array &$complete_form);

  /**
   * Ensures an include file is loaded whenever the form is processed.
   *
   * Example:
   * @code
   *   // Load node.admin.inc from Node module.
   *   $form_state->loadInclude('node', 'inc', 'node.admin');
   * @endcode
   *
   * Use this function instead of module_load_include() from inside a form
   * constructor or any form processing logic as it ensures that the include file
   * is loaded whenever the form is processed. In contrast to using
   * module_load_include() directly, this method makes sure the include file is
   * correctly loaded also if the form is cached.
   *
   * @param string $module
   *   The module to which the include file belongs.
   * @param string $type
   *   The include file's type (file extension).
   * @param string|null $name
   *   (optional) The base file name (without the $type extension). If omitted,
   *   $module is used; i.e., resulting in "$module.$type" by default.
   *
   * @return string|false
   *   The filepath of the loaded include file, or FALSE if the include file was
   *   not found or has been loaded already.
   *
   * @see module_load_include()
   */
  public function loadInclude($module, $type, $name = NULL);

  /**
   * Returns an array representation of the cacheable portion of the form state.
   *
   * @return array
   *   The cacheable portion of the form state.
   */
  public function getCacheableArray();

  /**
   * Sets the value of the form state.
   *
   * @param array $form_state_additions
   *   An array of values to add to the form state.
   *
   * @return $this
   */
  public function setFormState(array $form_state_additions);

  /**
   * Sets a value to an arbitrary property if it does not exist yet.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The data to store.
   *
   * @return $this
   */
  public function setIfNotExists($property, $value);

  /**
   * Sets a response for this form.
   *
   * If a response is set, it will be used during processing and returned
   * directly. The form will not be rebuilt or redirected.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to return.
   *
   * @return $this
   */
  public function setResponse(Response $response);

  /**
   * Sets the redirect for the form.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options. See
   *   \Drupal\Core\Url for the available keys.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormSubmitterInterface::redirectForm()
   */
  public function setRedirect($route_name, array $route_parameters = array(), array $options = array());

  /**
   * Sets the redirect URL for the form.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to redirect to.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormSubmitterInterface::redirectForm()
   */
  public function setRedirectUrl(Url $url);

  /**
   * Gets the value to use for redirecting after the form has been executed.
   *
   * @see \Drupal\Core\Form\FormSubmitterInterface::redirectForm()
   *
   * @return mixed
   *   The value will be one of the following:
   *   - A fully prepared \Symfony\Component\HttpFoundation\RedirectResponse.
   *   - An instance of \Drupal\Core\Url to use for the redirect.
   *   - NULL, to signify that no redirect was specified and that the current
   *     path should be used for the redirect.
   *   - FALSE, to signify that no redirect should take place.
   */
  public function getRedirect();

  /**
   * Gets any arbitrary property.
   *
   * @param string $property
   *   The property to retrieve.
   *
   * @return mixed
   *   A reference to the value for that property, or NULL if the property does
   *   not exist.
   */
  public function &get($property);

  /**
   * Sets a value to an arbitrary property.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function set($property, $value);

  /**
   * @param string $property
   *   The property to use for the value.
   */
  public function has($property);

  /**
   * Adds a value to the build info.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function addBuildInfo($property, $value);

  /**
   * Returns the form values as they were submitted by the user.
   *
   * These are raw and unvalidated, so should not be used without a thorough
   * understanding of security implications. In almost all cases, code should
   * use self::getValues() and self::getValue() exclusively.
   *
   * @return array
   *   An associative array of values submitted to the form.
   */
  public function &getUserInput();

  /**
   * Sets the form values as though they were submitted by a user.
   *
   * @param array $user_input
   *   An associative array of raw and unvalidated values.
   *
   * @return $this
   */
  public function setUserInput(array $user_input);

  /**
   * Returns the submitted and sanitized form values.
   *
   * @return array
   *   An associative array of values submitted to the form.
   */
  public function &getValues();

  /**
   * Returns the submitted form value for a specific key.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key, or NULL.
   */
  public function &getValue($key, $default = NULL);

  /**
   * Sets the submitted form value for a specific key.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will use $values[$key] = $value. If $key is an array, each
   *   element of the array will be used as a nested key. If
   *   $key = array('foo', 'bar') it will use $values['foo']['bar'] = $value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setValue($key, $value);

  /**
   * Removes a specific key from the submitted form values.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will use unset($values[$key]). If $key is an array, each
   *   element of the array will be used as a nested key. If
   *   $key = array('foo', 'bar') it will use unset($values['foo']['bar']).
   *
   * @return $this
   */
  public function unsetValue($key);

  /**
   * Determines if a specific key is present in the submitted form values.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return isset($values[$key]). If $key is an array, each
   *   element of the array will be used as a nested key. If
   *   $key = array('foo', 'bar') it will return isset($values['foo']['bar']).
   *
   * @return bool
   *   TRUE if the $key is set, FALSE otherwise.
   */
  public function hasValue($key);

  /**
   * Determines if a specific key has a value in the submitted form values.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return empty($values[$key]). If $key is an array, each
   *   element of the array will be used as a nested key. If
   *   $key = array('foo', 'bar') it will return empty($values['foo']['bar']).
   *
   * @return bool
   *   TRUE if the $key has no value, FALSE otherwise.
   */
  public function isValueEmpty($key);

  /**
   * Changes submitted form values during form validation.
   *
   * Use this function to change the submitted value of a form element in a form
   * validation function, so that the changed value persists in $form_state
   * through to the submission handlers.
   *
   * Note that form validation functions are specified in the '#validate'
   * component of the form array (the value of $form['#validate'] is an array of
   * validation function names). If the form does not originate in your module,
   * you can implement hook_form_FORM_ID_alter() to add a validation function
   * to $form['#validate'].
   *
   * @param array $element
   *   The form element that should have its value updated; in most cases you
   *   can just pass in the element from the $form array, although the only
   *   component that is actually used is '#parents'. If constructing yourself,
   *   set $element['#parents'] to be an array giving the path through the form
   *   array's keys to the element whose value you want to update. For instance,
   *   if you want to update the value of $form['elem1']['elem2'], which should
   *   be stored in $form_state->getValue(array('elem1', 'elem2')), you would
   *   set $element['#parents'] = array('elem1','elem2').
   * @param mixed $value
   *   The new value for the form element.
   *
   * @return $this
   */
  public function setValueForElement($element, $value);

  /**
   * Determines if any forms have any errors.
   *
   * @return bool
   *   TRUE if any form has any errors, FALSE otherwise.
   */
  public static function hasAnyErrors();

  /**
   * Files an error against a form element.
   *
   * When a validation error is detected, the validator calls this method to
   * indicate which element needs to be changed and provide an error message.
   * This causes the Form API to not execute the form submit handlers, and
   * instead to re-display the form to the user with the corresponding elements
   * rendered with an 'error' CSS class (shown as red by default).
   *
   * The standard behavior of this method can be changed if a button provides
   * the #limit_validation_errors property. Multistep forms not wanting to
   * validate the whole form can set #limit_validation_errors on buttons to
   * limit validation errors to only certain elements. For example, pressing the
   * "Previous" button in a multistep form should not fire validation errors
   * just because the current step has invalid values. If
   * #limit_validation_errors is set on a clicked button, the button must also
   * define a #submit property (may be set to an empty array). Any #submit
   * handlers will be executed even if there is invalid input, so extreme care
   * should be taken with respect to any actions taken by them. This is
   * typically not a problem with buttons like "Previous" or "Add more" that do
   * not invoke persistent storage of the submitted form values. Do not use the
   * #limit_validation_errors property on buttons that trigger saving of form
   * values to the database.
   *
   * The #limit_validation_errors property is a list of "sections" within
   * $form_state->getValues() that must contain valid values. Each "section" is
   * an array with the ordered set of keys needed to reach that part of
   * $form_state->getValues() (i.e., the #parents property of the element).
   *
   * Example 1: Allow the "Previous" button to function, regardless of whether
   * any user input is valid.
   *
   * @code
   *   $form['actions']['previous'] = array(
   *     '#type' => 'submit',
   *     '#value' => t('Previous'),
   *     '#limit_validation_errors' => array(),       // No validation.
   *     '#submit' => array('some_submit_function'),  // #submit required.
   *   );
   * @endcode
   *
   * Example 2: Require some, but not all, user input to be valid to process the
   * submission of a "Previous" button.
   *
   * @code
   *   $form['actions']['previous'] = array(
   *     '#type' => 'submit',
   *     '#value' => t('Previous'),
   *     '#limit_validation_errors' => array(
   *       // Validate $form_state->getValue('step1').
   *       array('step1'),
   *       // Validate $form_state->getValue(array('foo', 'bar')).
   *       array('foo', 'bar'),
   *     ),
   *     '#submit' => array('some_submit_function'), // #submit required.
   *   );
   * @endcode
   *
   * This will require $form_state->getValue('step1') and everything within it
   * (for example, $form_state->getValue(array('step1', 'choice'))) to be valid,
   * so calls to self::setErrorByName('step1', $message) or
   * self::setErrorByName('step1][choice', $message) will prevent the submit
   * handlers from running, and result in the error message being displayed to
   * the user. However, calls to self::setErrorByName('step2', $message) and
   * self::setErrorByName('step2][groupX][choiceY', $message) will be
   * suppressed, resulting in the message not being displayed to the user, and
   * the submit handlers will run despite $form_state->getValue('step2') and
   * $form_state->getValue(array('step2', 'groupX', 'choiceY')) containing
   * invalid values. Errors for an invalid $form_state->getValue('foo') will be
   * suppressed, but errors flagging invalid values for
   * $form_state->getValue(array('foo', 'bar')) and everything within it will
   * be flagged and submission prevented.
   *
   * Partial form validation is implemented by suppressing errors rather than by
   * skipping the input processing and validation steps entirely, because some
   * forms have button-level submit handlers that call Drupal API functions that
   * assume that certain data exists within $form_state->getValues(), and while
   * not doing anything with that data that requires it to be valid, PHP errors
   * would be triggered if the input processing and validation steps were fully
   * skipped.
   *
   * @param string $name
   *   The name of the form element. If the #parents property of your form
   *   element is array('foo', 'bar', 'baz') then you may set an error on 'foo'
   *   or 'foo][bar][baz'. Setting an error on 'foo' sets an error for every
   *   element where the #parents array starts with 'foo'.
   * @param string $message
   *   (optional) The error message to present to the user.
   *
   * @return $this
   */
  public function setErrorByName($name, $message = '');

  /**
   * Flags an element as having an error.
   *
   * @param array $element
   *   The form element.
   * @param string $message
   *   (optional) The error message to present to the user.
   *
   * @return $this
   */
  public function setError(&$element, $message = '');

  /**
   * Clears all errors against all form elements made by self::setErrorByName().
   */
  public function clearErrors();

  /**
   * Returns an associative array of all errors.
   *
   * @return array
   *   An array of all errors, keyed by the name of the form element.
   */
  public function getErrors();

  /**
   * Returns the error message filed against the given form element.
   *
   * Form errors higher up in the form structure override deeper errors as well
   * as errors on the element itself.
   *
   * @param array $element
   *   The form element to check for errors.
   *
   * @return string|null
   *   Either the error message for this element or NULL if there are no errors.
   */
  public function getError($element);

  /**
   * Sets the form to be rebuilt after processing.
   *
   * @param bool $rebuild
   *   (optional) Whether the form should be rebuilt or not. Defaults to TRUE.
   *
   * @return $this
   */
  public function setRebuild($rebuild = TRUE);

  /**
   * Converts support notations for a form callback to a valid callable.
   *
   * Specifically, supports methods on the form/callback object as strings when
   * they start with ::, for example "::submitForm()".
   *
   * @param string|array $callback
   *   The callback.
   *
   * @return array|string
   *   A valid callable.
   */
  public function prepareCallback($callback);

  /**
   * Returns the form object that is responsible for building this form.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   The form object.
   */
  public function getFormObject();

}
