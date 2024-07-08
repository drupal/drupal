<?php

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
 * 'cache' or 'rebuild' flag is set. See \Drupal\Core\Form\FormState for
 * documentation of the available flags.
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
   * Use this function instead of \Drupal::moduleHandler()->loadInclude()
   * from inside a form constructor or any form processing logic as it ensures
   * that the include file is loaded whenever the form is processed. In contrast
   * to using \Drupal::moduleHandler()->loadInclude() directly, this method
   * makes sure the include file is correctly loaded also if the form is cached.
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
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::loadInclude()
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
   * Gets a response for this form.
   *
   * If a response is set, it will be used during processing and returned
   * directly. The form will not be rebuilt or redirected.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response to return, or NULL.
   */
  public function getResponse();

  /**
   * Sets the redirect for the form.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options containing the
   *   same values accepted from \Drupal\Core\Url::fromUri() for $options.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormSubmitterInterface::redirectForm()
   * @see \Drupal\Core\Url::fromUri()
   */
  public function setRedirect($route_name, array $route_parameters = [], array $options = []);

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
   * Determines whether the redirect respects the destination query parameter.
   *
   * @param bool $status
   *   (optional) TRUE if the redirect should take precedence over the
   *   destination query parameter. FALSE if not. Defaults to TRUE.
   *
   * @return $this
   */
  public function setIgnoreDestination(bool $status = TRUE);

  /**
   * Gets whether the redirect respects the destination query parameter.
   *
   * @return bool
   *   TRUE if the redirect should take precedence over the destination query
   *   parameter.
   */
  public function getIgnoreDestination(): bool;

  /**
   * Sets the entire set of arbitrary data.
   *
   * @param array $storage
   *   The entire set of arbitrary data to store for this form.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormStateInterface::get()
   * @see \Drupal\Core\Form\FormStateInterface::set()
   * @see \Drupal\Core\Form\FormStateInterface::has()
   * @see \Drupal\Core\Form\FormStateInterface::getStorage()
   */
  public function setStorage(array $storage);

  /**
   * Returns the entire set of arbitrary data.
   *
   * @return array
   *   The entire set of arbitrary data to store for this form.
   *
   * @see \Drupal\Core\Form\FormStateInterface::get()
   * @see \Drupal\Core\Form\FormStateInterface::set()
   * @see \Drupal\Core\Form\FormStateInterface::has()
   * @see \Drupal\Core\Form\FormStateInterface::setStorage()
   */
  public function &getStorage();

  /**
   * Gets the value for a property in the form state storage.
   *
   * @param string|array $property
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $property is a string, it will return $storage[$property]. If $property
   *   is an array, each element of the array will be used as a nested key. If
   *   $property = ['foo', 'bar'] it will return $storage['foo']['bar'].
   *
   * @return mixed
   *   A reference to the value for that property, or NULL if the property does
   *   not exist.
   *
   * @see \Drupal\Core\Form\FormStateInterface::set()
   * @see \Drupal\Core\Form\FormStateInterface::has()
   * @see \Drupal\Core\Form\FormStateInterface::getStorage()
   * @see \Drupal\Core\Form\FormStateInterface::setStorage()
   */
  public function &get($property);

  /**
   * Sets the value for a property in the form state storage.
   *
   * @param string|array $property
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $property is a string, it will use $storage[$property] = $value. If
   *   $property is an array, each element of the array will be used as a nested
   *   key. If $property = ['foo', 'bar'] it will use
   *   $storage['foo']['bar'] = $value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormStateInterface::get()
   * @see \Drupal\Core\Form\FormStateInterface::has()
   * @see \Drupal\Core\Form\FormStateInterface::getStorage()
   * @see \Drupal\Core\Form\FormStateInterface::setStorage()
   */
  public function set($property, $value);

  /**
   * Determines if a property is present in the form state storage.
   *
   * @param string|array $property
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $property is a string, it will return isset($storage[$property]). If
   *   $property is an array, each element of the array will be used as a nested
   *   key. If $property = ['foo', 'bar'] it will return
   *   isset($storage['foo']['bar']).
   *
   * @see \Drupal\Core\Form\FormStateInterface::get()
   * @see \Drupal\Core\Form\FormStateInterface::set()
   * @see \Drupal\Core\Form\FormStateInterface::getStorage()
   * @see \Drupal\Core\Form\FormStateInterface::setStorage()
   */
  public function has($property);

  /**
   * Sets the build info for the form.
   *
   * @param array $build_info
   *   An array of build info.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormState::$build_info
   */
  public function setBuildInfo(array $build_info);

  /**
   * Returns the build info for the form.
   *
   * @return array
   *   An array of build info.
   *
   * @see \Drupal\Core\Form\FormState::$build_info
   */
  public function getBuildInfo();

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
   * These are raw and non validated, so should not be used without a thorough
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
   *   An associative array of raw and non validated values.
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
   *   of the array will be used as a nested key. If $key = ['foo', 'bar']
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key, or NULL.
   */
  public function &getValue($key, $default = NULL);

  /**
   * Sets the submitted form values.
   *
   * This should be avoided, since these values have been validated already. Use
   * self::setUserInput() instead.
   *
   * @param array $values
   *   The multi-dimensional associative array of form values.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Sets the submitted form value for a specific key.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will use $values[$key] = $value. If $key is an array, each
   *   element of the array will be used as a nested key. If
   *   $key = ['foo', 'bar'] it will use $values['foo']['bar'] = $value.
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
   *   $key = ['foo', 'bar'] it will use unset($values['foo']['bar']).
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
   *   $key = ['foo', 'bar'] it will return isset($values['foo']['bar']).
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
   *   $key = ['foo', 'bar'] it will return empty($values['foo']['bar']).
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
   *   be stored in $form_state->getValue(['elem1', 'elem2']), you would
   *   set $element['#parents'] = ['elem1','elem2'].
   * @param mixed $value
   *   The new value for the form element.
   *
   * @return $this
   */
  public function setValueForElement(array $element, $value);

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
   *   $form['actions']['previous'] = [
   *     '#type' => 'submit',
   *     '#value' => t('Previous'),
   *     '#limit_validation_errors' => [],       // No validation.
   *     '#submit' => ['some_submit_function'],  // #submit required.
   *   ];
   * @endcode
   *
   * Example 2: Require some, but not all, user input to be valid to process the
   * submission of a "Previous" button.
   *
   * @code
   *   $form['actions']['previous'] = [
   *     '#type' => 'submit',
   *     '#value' => t('Previous'),
   *     '#limit_validation_errors' => [
   *       // Validate $form_state->getValue('step1').
   *       ['step1'],
   *       // Validate $form_state->getValue(['foo', 'bar']).
   *       ['foo', 'bar'],
   *     ),
   *     '#submit' => ['some_submit_function'], // #submit required.
   *   );
   * @endcode
   *
   * This will require $form_state->getValue('step1') and everything within it
   * (for example, $form_state->getValue(['step1', 'choice'])) to be valid,
   * so calls to self::setErrorByName('step1', $message) or
   * self::setErrorByName('step1][choice', $message) will prevent the submit
   * handlers from running, and result in the error message being displayed to
   * the user. However, calls to self::setErrorByName('step2', $message) and
   * self::setErrorByName('step2][groupX][choiceY', $message) will be
   * suppressed, resulting in the message not being displayed to the user, and
   * the submit handlers will run despite $form_state->getValue('step2') and
   * $form_state->getValue(['step2', 'groupX', 'choiceY']) containing
   * invalid values. Errors for an invalid $form_state->getValue('foo') will be
   * suppressed, but errors flagging invalid values for
   * $form_state->getValue(['foo', 'bar']) and everything within it will
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
   *   element is ['foo', 'bar', 'baz'] then you may set an error on 'foo'
   *   or 'foo][bar][baz'. Setting an error on 'foo' sets an error for every
   *   element where the #parents array starts with 'foo'.
   * @param string|\Stringable $message
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
   * @param string|\Stringable $message
   *   (optional) The error message to present to the user.
   *
   * @return $this
   */
  public function setError(array &$element, $message = '');

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
  public function getError(array $element);

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
   * Determines if the form should be rebuilt after processing.
   *
   * @return bool
   *   TRUE if the form should be rebuilt, FALSE otherwise.
   */
  public function isRebuilding();

  /**
   * Flags the form state as having or not an invalid token.
   *
   * @param bool $invalid_token
   *   Whether the form has an invalid token.
   *
   * @return $this
   */
  public function setInvalidToken($invalid_token);

  /**
   * Determines if the form has an invalid token.
   *
   * @return bool
   *   TRUE if the form has an invalid token, FALSE otherwise.
   */
  public function hasInvalidToken();

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

  /**
   * Sets the form object that is responsible for building this form.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The form object.
   *
   * @return $this
   */
  public function setFormObject(FormInterface $form_object);

  /**
   * Sets this form to always be processed.
   *
   * This should only be used on RESTful GET forms that do NOT write data, as
   * this could lead to security issues. It is useful so that searches do not
   * need to have a form_id in their query arguments to trigger the search.
   *
   * @param bool $always_process
   *   TRUE if the form should always be processed, FALSE otherwise.
   *
   * @return $this
   */
  public function setAlwaysProcess($always_process = TRUE);

  /**
   * Determines if this form should always be processed.
   *
   * @return bool
   *   TRUE if the form should always be processed, FALSE otherwise.
   */
  public function getAlwaysProcess();

  /**
   * Stores the submit and button elements for the form.
   *
   * @param array $buttons
   *   The submit and button elements.
   *
   * @return $this
   */
  public function setButtons(array $buttons);

  /**
   * Returns the submit and button elements for the form.
   *
   * @return array
   *   The submit and button elements.
   */
  public function getButtons();

  /**
   * Sets this form to be cached.
   *
   * @param bool $cache
   *   TRUE if the form should be cached, FALSE otherwise.
   *
   * @return $this
   *
   * @throws \LogicException
   *   If the current request is using an HTTP method that must not change
   *   state (e.g., GET).
   */
  public function setCached($cache = TRUE);

  /**
   * Determines if the form should be cached.
   *
   * @return bool
   *   TRUE if the form should be cached, FALSE otherwise.
   */
  public function isCached();

  /**
   * Prevents the form from being cached.
   *
   * @return $this
   */
  public function disableCache();

  /**
   * Sets that the form was submitted and has been processed and executed.
   *
   * @return $this
   */
  public function setExecuted();

  /**
   * Determines if the form was submitted and has been processed and executed.
   *
   * @return bool
   *   TRUE if the form was submitted and has been processed and executed.
   */
  public function isExecuted();

  /**
   * Sets references to details elements to render them within vertical tabs.
   *
   * @param array $groups
   *   References to details elements to render them within vertical tabs.
   *
   * @return $this
   */
  public function setGroups(array $groups);

  /**
   * Returns references to details elements to render them within vertical tabs.
   *
   * @return array
   */
  public function &getGroups();

  /**
   * Sets that this form has a file element.
   *
   * @param bool $has_file_element
   *   Whether this form has a file element.
   *
   * @return $this
   */
  public function setHasFileElement($has_file_element = TRUE);

  /**
   * Returns whether this form has a file element.
   *
   * @return bool
   *   Whether this form has a file element.
   */
  public function hasFileElement();

  /**
   * Sets the limited validation error sections.
   *
   * @param array|null $limit_validation_errors
   *   The limited validation error sections.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormState::$limit_validation_errors
   */
  public function setLimitValidationErrors($limit_validation_errors);

  /**
   * Retrieves the limited validation error sections.
   *
   * @return array|null
   *   The limited validation error sections.
   *
   * @see \Drupal\Core\Form\FormState::$limit_validation_errors
   */
  public function getLimitValidationErrors();

  /**
   * Sets the HTTP method to use for the form's submission.
   *
   * This is what the form's "method" attribute should be, not necessarily what
   * the current request's HTTP method is. For example, a form can have a
   * method attribute of POST, but the request that initially builds it uses
   * GET.
   *
   * @param string $method
   *   Either "GET" or "POST". Other HTTP methods are not valid form submission
   *   methods.
   *
   * @see \Drupal\Core\Form\FormState::$method
   * @see \Drupal\Core\Form\FormStateInterface::setRequestMethod()
   *
   * @return $this
   */
  public function setMethod($method);

  /**
   * Sets the HTTP method used by the request that is building the form.
   *
   * @param string $method
   *   Can be any valid HTTP method, such as GET, POST, HEAD, etc.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormStateInterface::setMethod()
   */
  public function setRequestMethod($method);

  /**
   * Returns the HTTP form method.
   *
   * @param string $method_type
   *   The HTTP form method.
   *
   * @return bool
   *   TRUE if the HTTP form method matches.
   *
   * @see \Drupal\Core\Form\FormState::$method
   */
  public function isMethodType($method_type);

  /**
   * Enforces that validation is run.
   *
   * @param bool $must_validate
   *   If TRUE, validation will always be run.
   *
   * @return $this
   */
  public function setValidationEnforced($must_validate = TRUE);

  /**
   * Checks if validation is enforced.
   *
   * @return bool
   *   If TRUE, validation will always be run.
   */
  public function isValidationEnforced();

  /**
   * Prevents the form from redirecting.
   *
   * @param bool $no_redirect
   *   If TRUE, the form will not redirect.
   *
   * @return $this
   */
  public function disableRedirect($no_redirect = TRUE);

  /**
   * Determines if redirecting has been prevented.
   *
   * @return bool
   *   If TRUE, the form will not redirect.
   */
  public function isRedirectDisabled();

  /**
   * Sets that the form should process input.
   *
   * @param bool $process_input
   *   If TRUE, the form input will be processed.
   *
   * @return $this
   */
  public function setProcessInput($process_input = TRUE);

  /**
   * Determines if the form input will be processed.
   *
   * @return bool
   *   If TRUE, the form input will be processed.
   */
  public function isProcessingInput();

  /**
   * Sets that this form was submitted programmatically.
   *
   * @param bool $programmed
   *   If TRUE, the form was submitted programmatically.
   *
   * @return $this
   */
  public function setProgrammed($programmed = TRUE);

  /**
   * Returns if this form was submitted programmatically.
   *
   * @return bool
   *   If TRUE, the form was submitted programmatically.
   */
  public function isProgrammed();

  /**
   * Sets if this form submission should bypass #access.
   *
   * @param bool $programmed_bypass_access_check
   *   If TRUE, programmatic form submissions are processed without taking
   *   #access into account.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormState::$programmed_bypass_access_check
   */
  public function setProgrammedBypassAccessCheck($programmed_bypass_access_check = TRUE);

  /**
   * Determines if this form submission should bypass #access.
   *
   * @return bool
   *
   * @see \Drupal\Core\Form\FormState::$programmed_bypass_access_check
   */
  public function isBypassingProgrammedAccessChecks();

  /**
   * Sets the rebuild info.
   *
   * @param array $rebuild_info
   *   The rebuild info.
   *
   * @return $this
   *
   * @see \Drupal\Core\Form\FormState::$rebuild_info
   */
  public function setRebuildInfo(array $rebuild_info);

  /**
   * Gets the rebuild info.
   *
   * @return array
   *   The rebuild info.
   *
   * @see \Drupal\Core\Form\FormState::$rebuild_info
   */
  public function getRebuildInfo();

  /**
   * Adds a value to the rebuild info.
   *
   * @param string $property
   *   The property to use for the value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function addRebuildInfo($property, $value);

  /**
   * Sets the submit handlers.
   *
   * @param array $submit_handlers
   *   An array of submit handlers.
   *
   * @return $this
   */
  public function setSubmitHandlers(array $submit_handlers);

  /**
   * Gets the submit handlers.
   *
   * @return array
   *   An array of submit handlers.
   */
  public function getSubmitHandlers();

  /**
   * Sets that the form has been submitted.
   *
   * @return $this
   */
  public function setSubmitted();

  /**
   * Determines if the form has been submitted.
   *
   * @return bool
   *   TRUE if the form has been submitted, FALSE otherwise.
   */
  public function isSubmitted();

  /**
   * Sets temporary data.
   *
   * @param array $temporary
   *   Temporary data accessible during the current page request only.
   *
   * @return $this
   */
  public function setTemporary(array $temporary);

  /**
   * Gets temporary data.
   *
   * @return array
   *   Temporary data accessible during the current page request only.
   */
  public function getTemporary();

  /**
   * Gets an arbitrary value from temporary storage.
   *
   * @param string|array $key
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $key is a string, it will return $temporary[$key]. If $key is an array,
   *   each element of the array will be used as a nested key. If
   *   $key = ['foo', 'bar'] it will return $temporary['foo']['bar'].
   *
   * @return mixed
   *   A reference to the value for that key, or NULL if the property does
   *   not exist.
   */
  public function &getTemporaryValue($key);

  /**
   * Sets an arbitrary value in temporary storage.
   *
   * @param string|array $key
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $key is a string, it will use $temporary[$key] = $value. If $key is an
   *   array, each element of the array will be used as a nested key. If
   *   $key = ['foo', 'bar'] it will use $temporary['foo']['bar'] = $value.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTemporaryValue($key, $value);

  /**
   * Determines if a temporary value is present.
   *
   * @param string $key
   *   Properties are often stored as multi-dimensional associative arrays. If
   *   $key is a string, it will return isset($temporary[$key]). If $key is an
   *   array, each element of the array will be used as a nested key. If
   *   $key = ['foo', 'bar'] it will return isset($temporary['foo']['bar']).
   */
  public function hasTemporaryValue($key);

  /**
   * Sets the form element that triggered submission.
   *
   * @param array|null $triggering_element
   *   The form element that triggered submission, of NULL if there is none.
   *
   * @return $this
   */
  public function setTriggeringElement($triggering_element);

  /**
   * Gets the form element that triggered submission.
   *
   * @return array|null
   *   The form element that triggered submission, of NULL if there is none.
   */
  public function &getTriggeringElement();

  /**
   * Sets the validate handlers.
   *
   * @param array $validate_handlers
   *   An array of validate handlers.
   *
   * @return $this
   */
  public function setValidateHandlers(array $validate_handlers);

  /**
   * Gets the validate handlers.
   *
   * @return array
   *   An array of validate handlers.
   */
  public function getValidateHandlers();

  /**
   * Sets that validation has been completed.
   *
   * @param bool $validation_complete
   *   TRUE if validation is complete, FALSE otherwise.
   *
   * @return $this
   */
  public function setValidationComplete($validation_complete = TRUE);

  /**
   * Determines if validation has been completed.
   *
   * @return bool
   *   TRUE if validation is complete, FALSE otherwise.
   */
  public function isValidationComplete();

  /**
   * Gets the keys of the form values that will be cleaned.
   *
   * @return array
   *   An array of form value keys to be cleaned.
   */
  public function getCleanValueKeys();

  /**
   * Sets the keys of the form values that will be cleaned.
   *
   * @param array $keys
   *   An array of form value keys to be cleaned.
   *
   * @return $this
   */
  public function setCleanValueKeys(array $keys);

  /**
   * Adds a key to the array of form values that will be cleaned.
   *
   * @param string $key
   *   The form value key to be cleaned.
   *
   * @return $this
   */
  public function addCleanValueKey($key);

  /**
   * Removes internal Form API elements and buttons from submitted form values.
   *
   * This function can be used when a module wants to store all submitted form
   * values, for example, by serializing them into a single database column. In
   * such cases, all internal Form API values and all form button elements
   * should not be contained, and this function allows their removal before the
   * module proceeds to storage. Next to button elements, the following internal
   * values are removed by default.
   * - form_id
   * - form_token
   * - form_build_id
   * - op
   *
   * @return $this
   */
  public function cleanValues();

}
