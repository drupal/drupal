<?php

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores information about the state of a form.
 */
class FormState implements FormStateInterface {

  use FormStateValuesTrait;

  /**
   * Tracks if any errors have been set on any form.
   *
   * @var bool
   */
  protected static $anyErrors = FALSE;

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
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $complete_form;

  /**
   * An associative array of information stored by Form API.
   *
   * This associative array is necessary to build and rebuild the form from
   * cache when the original context may no longer be available:
   *   - callback: The actual callback to be used to retrieve the form array.
   *     Can be any callable. If none is provided $form_id is used as the name
   *     of a function to call instead.
   *   - args: A list of arguments to pass to the form constructor.
   *   - files: An optional array defining include files that need to be loaded
   *     for building the form. Each array entry may be the path to a file or
   *     another array containing values for the parameters 'type', 'module' and
   *     'name' as needed by \Drupal::moduleHandler()->loadInclude(). The files
   *     listed here are automatically loaded by
   *     \Drupal::formBuilder()->getCache(). By default the current menu router
   *     item's 'file' definition is added, if any. Use self::loadInclude() to
   *     add include files from a form constructor.
   *   - form_id: Identification of the primary form being constructed and
   *     processed.
   *   - base_form_id: Identification for a base form, as declared in the form
   *     class's \Drupal\Core\Form\BaseFormIdInterface::getBaseFormId() method.
   *   - immutable: If this flag is set to TRUE, a new form build id is
   *     generated when the form is loaded from the cache. If it is subsequently
   *     saved to the cache again, it will have another cache id and therefore
   *     the original form and form-state will remain unaltered. This is
   *     important when page caching is enabled in order to prevent form state
   *     from leaking between anonymous users.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $build_info = [
    'args' => [],
    'files' => [],
  ];

  /**
   * Similar to self::$build_info.
   *
   * But pertaining to \Drupal\Core\Form\FormBuilderInterface::rebuildForm().
   *
   * This property is uncacheable.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $rebuild_info = [];

  /**
   * Determines whether the form is rebuilt.
   *
   * Normally, after the entire form processing is completed and submit handlers
   * have run, a form is considered to be done and
   * \Drupal\Core\Form\FormSubmitterInterface::redirectForm() will redirect the
   * user to a new page using a GET request (so a browser refresh does not
   * re-submit the form). However, if 'rebuild' has been set to TRUE, then a new
   * copy of the form is immediately built and sent to the browser, instead of a
   * redirect. This is used for multi-step forms, such as wizards and
   * confirmation forms. Normally, self::$rebuild is set by a submit handler,
   * since it is usually logic within a submit handler that determines whether a
   * form is done or requires another step. However, a validation handler may
   * already set self::$rebuild to cause the form processing to bypass submit
   * handlers and rebuild the form instead, even if there are no validation
   * errors.
   *
   * This property is uncacheable.
   *
   * @see self::setRebuild()
   *
   * @var bool
   */
  protected $rebuild = FALSE;

  /**
   * Determines if only safe element value callbacks are called.
   *
   * If set to TRUE the form will skip calling form element value callbacks,
   * except for a select list of callbacks provided by Drupal core that are
   * known to be safe.
   *
   * This property is uncacheable.
   *
   * @see self::setInvalidToken()
   *
   * @var bool
   */
  protected $invalidToken = FALSE;

  /**
   * The response object.
   *
   * Used when a form needs to return some kind of a
   * \Symfony\Component\HttpFoundation\Response object, e.g., a
   * \Symfony\Component\HttpFoundation\BinaryFileResponse when triggering a
   * file download. If you use self::setRedirect() or self::setRedirectUrl(),
   * it will be used to build a
   * \Symfony\Component\HttpFoundation\RedirectResponse and will populate this
   * key.
   *
   * @var \Symfony\Component\HttpFoundation\Response|null
   */
  protected $response;

  /**
   * Used to ignore destination when redirecting.
   *
   * @var bool
   */
  protected bool $ignoreDestination = FALSE;

  /**
   * Used to redirect the form on submission.
   *
   * @see self::getRedirect()
   *
   * This property is uncacheable.
   *
   * @var \Drupal\Core\Url|\Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  protected $redirect;

  /**
   * If set to TRUE the form will NOT perform a redirect.
   *
   * Redirect will not be performed, even if self::$redirect is set.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $no_redirect;

  /**
   * The HTTP form method to use for finding the input for this form.
   *
   * May be 'POST' or 'GET'. Defaults to 'POST'. Note that 'GET' method forms do
   * not use form ids so are always considered to be submitted, which can have
   * unexpected effects. The 'GET' method should only be used on forms that do
   * not change data, as that is exclusively the domain of 'POST.'
   *
   * This property is uncacheable.
   *
   * @var string
   */
  protected $method = 'POST';

  /**
   * The HTTP method used by the request building or processing this form.
   *
   * May be any valid HTTP method. Defaults to 'GET', because even though
   * $method is 'POST' for most forms, the form's initial build is usually
   * performed as part of a GET request.
   *
   * This property is uncacheable.
   *
   * @var string
   */
  protected $requestMethod = 'GET';

  /**
   * Determines if the unprocessed form structure is cached.
   *
   * If set to TRUE the original, unprocessed form structure will be cached,
   * which allows the entire form to be rebuilt from cache. A typical form
   * workflow involves two page requests; first, a form is built and rendered
   * for the user to fill in. Then, the user fills the form in and submits it,
   * triggering a second page request in which the form must be built and
   * processed. By default, $form and $form_state are built from scratch during
   * each of these page requests. Often, it is necessary or desired to persist
   * the $form and $form_state variables from the initial page request to the
   * one that processes the submission. 'cache' can be set to TRUE to do this.
   * A prominent example is an Ajax-enabled form, in which
   * \Drupal\Core\Render\Element\RenderElementBase::processAjaxForm()
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
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $no_cache;

  /**
   * An associative array of values submitted to the form.
   *
   * The validation functions and submit functions use this array for nearly all
   * their decision making. (Note that #tree determines whether the values are a
   * flat array or an array whose structure parallels the $form array. See
   * \Drupal\Core\Render\Element\FormElementBase for more information.)
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $values = [];

  /**
   * An associative array of form value keys to be removed by cleanValues().
   *
   * Any values that are temporary but must still be displayed as values in
   * the rendered form should be added to this array using addCleanValueKey().
   * Initialized with internal Form API values.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $cleanValueKeys = [
    'form_id',
    'form_token',
    'form_build_id',
    'op',
  ];

  /**
   * The array of values as they were submitted by the user.
   *
   * These are raw and non validated, so should not be used without a thorough
   * understanding of security implications. In almost all cases, code should
   * use the data in the 'values' array exclusively. The most common use of this
   * key is for multi-step forms that need to clear some of the user input when
   * setting 'rebuild'. The values correspond to \Drupal::request()->request or
   * \Drupal::request()->query, depending on the 'method' chosen.
   *
   * This property is uncacheable.
   *
   * @var array|null
   *   The submitted user input array, or NULL if no input was submitted yet.
   */
  protected $input;

  /**
   * If TRUE and the method is GET, a form_id is not necessary.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $always_process;

  /**
   * Indicates if a validation will be forced.
   *
   * Ordinarily, a form is only validated once, but there are times when a form
   * is resubmitted internally and should be validated again. Setting this to
   * TRUE will force that to happen. This is most likely to occur during Ajax
   * operations.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $must_validate;

  /**
   * Indicates if the form was submitted programmatically.
   *
   * If TRUE, the form was submitted programmatically, usually invoked via
   * \Drupal\Core\Form\FormBuilderInterface::submitForm(). Defaults to FALSE.
   *
   * @var bool
   */
  protected $programmed = FALSE;

  /**
   * Indicates if programmatic form submissions bypasses #access check.
   *
   * If TRUE, programmatic form submissions are processed without taking #access
   * into account. Set this to FALSE when submitting a form programmatically
   * with values that may have been input by the user executing the current
   * request; this will cause #access to be respected as it would on a normal
   * form submission. Defaults to TRUE.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $programmed_bypass_access_check = TRUE;

  /**
   * Indicates correct form submission.
   *
   * TRUE signifies correct form submission. This is always TRUE for programmed
   * forms coming from \Drupal\Core\Form\FormBuilderInterface::submitForm() (see
   * 'programmed' key), or if the form_id coming from the
   * \Drupal::request()->request data is set and matches the current form_id.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
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
   * The form element that triggered submission.
   *
   * This may or may not be a button (in the case of Ajax forms). This key is
   * often used to distinguish between various buttons in a submit handler, and
   * is also used in Ajax handlers.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $triggering_element;

  /**
   * Indicates a file element is present.
   *
   * If TRUE, there is a file element and Form API will set the appropriate
   * 'enctype' HTML attribute on the form.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $has_file_element;

  /**
   * Contains references to details elements to render them within vertical tabs.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $groups = [];

  /**
   * The storage.
   *
   * This is not a special key, and no specific support is provided for it in
   * the Form API. By tradition it was the location where application-specific
   * data was stored for communication between the submit, validation, and form
   * builder functions, especially in a multi-step-style form. Form
   * implementations may use any key(s) within $form_state (other than the keys
   * listed here and other reserved ones used by Form API internals) for this
   * kind of storage. The recommended way to ensure that the chosen key doesn't
   * conflict with ones used by the Form API or other modules is to use the
   * module name as the key name or a prefix for the key name. For example, the
   * entity form classes use $this->entity in entity forms, or
   * $form_state->getFormObject()->getEntity() outside the controller, to store
   * information about the entity being edited, and this information stays
   * available across successive clicks of the "Preview" button (if available)
   * as well as when the "Save" button is finally clicked.
   *
   * @var array
   */
  protected $storage = [];

  /**
   * A list containing copies of all submit and button elements in the form.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $buttons = [];

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
  protected $temporary = [];

  /**
   * Tracks if the form has finished validation.
   *
   * This property is uncacheable.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $validation_complete = FALSE;

  /**
   * Contains errors for this form.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Stores which errors should be limited during validation.
   *
   * An array of "sections" within which user input must be valid. If the
   * element is within one of these sections, the error must be recorded.
   * Otherwise, it can be suppressed. self::$limit_validation_errors can be an
   * empty array, in which case all errors are suppressed. For example, a
   * "Previous" button might want its submit action to be triggered even if none
   * of the submitted values are valid.
   *
   * This property is uncacheable.
   *
   * @var array|null
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $limit_validation_errors;

  /**
   * Stores the gathered validation handlers.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $validate_handlers = [];

  /**
   * Stores the gathered submission handlers.
   *
   * This property is uncacheable.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $submit_handlers = [];

  /**
   * {@inheritdoc}
   */
  public function setFormState(array $form_state_additions) {
    foreach ($form_state_additions as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
      else {
        $this->set($key, $value);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlwaysProcess($always_process = TRUE) {
    $this->always_process = (bool) $always_process;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlwaysProcess() {
    return $this->always_process;
  }

  /**
   * {@inheritdoc}
   */
  public function setButtons(array $buttons) {
    $this->buttons = $buttons;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return $this->buttons;
  }

  /**
   * {@inheritdoc}
   */
  public function setCached($cache = TRUE) {
    // Persisting $form_state is a side-effect disallowed during a "safe" HTTP
    // method.
    if ($cache && $this->isRequestMethodSafe()) {
      throw new \LogicException(sprintf('Form state caching on %s requests is not allowed.', $this->requestMethod));
    }

    $this->cache = (bool) $cache;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCached() {
    return empty($this->no_cache) && $this->cache;
  }

  /**
   * {@inheritdoc}
   */
  public function disableCache() {
    $this->no_cache = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecuted() {
    $this->executed = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExecuted() {
    return $this->executed;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroups(array $groups) {
    $this->groups = $groups;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroups() {
    return $this->groups;
  }

  /**
   * {@inheritdoc}
   */
  public function setHasFileElement($has_file_element = TRUE) {
    $this->has_file_element = (bool) $has_file_element;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFileElement() {
    return $this->has_file_element;
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitValidationErrors($limit_validation_errors) {
    $this->limit_validation_errors = $limit_validation_errors;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitValidationErrors() {
    return $this->limit_validation_errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->method = strtoupper($method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isMethodType($method_type) {
    return $this->method === strtoupper($method_type);
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestMethod($method) {
    $this->requestMethod = strtoupper($method);
    return $this;
  }

  /**
   * Checks whether the request method is a "safe" HTTP method.
   *
   * Link below defines GET and HEAD as "safe" methods, meaning they SHOULD NOT
   * have side-effects, such as persisting $form_state changes.
   *
   * @return bool
   *
   * @see \Symfony\Component\HttpFoundation\Request::isMethodSafe()
   * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.1.1
   */
  protected function isRequestMethodSafe() {
    return in_array($this->requestMethod, ['GET', 'HEAD']);
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationEnforced($must_validate = TRUE) {
    $this->must_validate = (bool) $must_validate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationEnforced() {
    return $this->must_validate;
  }

  /**
   * {@inheritdoc}
   */
  public function disableRedirect($no_redirect = TRUE) {
    $this->no_redirect = (bool) $no_redirect;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRedirectDisabled() {
    return $this->no_redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessInput($process_input = TRUE) {
    $this->process_input = (bool) $process_input;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProcessingInput() {
    return $this->process_input;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammed($programmed = TRUE) {
    $this->programmed = (bool) $programmed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProgrammed() {
    return $this->programmed;
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammedBypassAccessCheck($programmed_bypass_access_check = TRUE) {
    $this->programmed_bypass_access_check = (bool) $programmed_bypass_access_check;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBypassingProgrammedAccessChecks() {
    return $this->programmed_bypass_access_check;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildInfo(array $rebuild_info) {
    $this->rebuild_info = $rebuild_info;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRebuildInfo() {
    return $this->rebuild_info;
  }

  /**
   * {@inheritdoc}
   */
  public function addRebuildInfo($property, $value) {
    $rebuild_info = $this->getRebuildInfo();
    $rebuild_info[$property] = $value;
    $this->setRebuildInfo($rebuild_info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorage(array $storage) {
    $this->storage = $storage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getStorage() {
    return $this->storage;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitHandlers(array $submit_handlers) {
    $this->submit_handlers = $submit_handlers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandlers() {
    return $this->submit_handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitted() {
    $this->submitted = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmitted() {
    return $this->submitted;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporary(array $temporary) {
    $this->temporary = $temporary;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemporary() {
    return $this->temporary;
  }

  /**
   * {@inheritdoc}
   */
  public function &getTemporaryValue($key) {
    $value = &NestedArray::getValue($this->temporary, (array) $key);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporaryValue($key, $value) {
    NestedArray::setValue($this->temporary, (array) $key, $value, TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTemporaryValue($key) {
    $exists = NULL;
    NestedArray::getValue($this->temporary, (array) $key, $exists);
    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggeringElement($triggering_element) {
    $this->triggering_element = $triggering_element;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getTriggeringElement() {
    return $this->triggering_element;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidateHandlers(array $validate_handlers) {
    $this->validate_handlers = $validate_handlers;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidateHandlers() {
    return $this->validate_handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationComplete($validation_complete = TRUE) {
    $this->validation_complete = (bool) $validation_complete;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationComplete() {
    return $this->validation_complete;
  }

  /**
   * {@inheritdoc}
   */
  public function loadInclude($module, $type, $name = NULL) {
    if (!isset($name)) {
      $name = $module;
    }
    $build_info = $this->getBuildInfo();
    if (!isset($build_info['files']["$module:$name.$type"])) {
      // Only add successfully included files to the form state.
      if ($result = $this->moduleLoadInclude($module, $type, $name)) {
        $build_info['files']["$module:$name.$type"] = [
          'type' => $type,
          'module' => $module,
          'name' => $name,
        ];
        $this->setBuildInfo($build_info);
        return $result;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableArray() {
    return [
      'build_info' => $this->getBuildInfo(),
      'response' => $this->getResponse(),
      'programmed' => $this->isProgrammed(),
      'programmed_bypass_access_check' => $this->isBypassingProgrammedAccessChecks(),
      'process_input' => $this->isProcessingInput(),
      'has_file_element' => $this->hasFileElement(),
      'storage' => $this->getStorage(),
      // Use the properties directly, since self::isCached() combines them and
      // cannot be relied upon.
      'cache' => $this->cache,
      'no_cache' => $this->no_cache,
    ];
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
  public function &get($property) {
    $value = &NestedArray::getValue($this->storage, (array) $property);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    NestedArray::setValue($this->storage, (array) $property, $value, TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function has($property) {
    $exists = NULL;
    NestedArray::getValue($this->storage, (array) $property, $exists);
    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildInfo(array $build_info) {
    $this->build_info = $build_info;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildInfo() {
    return $this->build_info;
  }

  /**
   * {@inheritdoc}
   */
  public function addBuildInfo($property, $value) {
    $build_info = $this->getBuildInfo();
    $build_info[$property] = $value;
    $this->setBuildInfo($build_info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getUserInput() {
    return $this->input;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserInput(array $user_input) {
    $this->input = $user_input;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponse(Response $response) {
    $this->response = $response;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirect($route_name, array $route_parameters = [], array $options = []) {
    $url = new Url($route_name, $route_parameters, $options);
    return $this->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUrl(Url $url) {
    $this->redirect = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    // Skip redirection for form submissions invoked via
    // \Drupal\Core\Form\FormBuilderInterface::submitForm().
    if ($this->isProgrammed()) {
      return FALSE;
    }
    // Skip redirection if rebuild is activated.
    if ($this->isRebuilding()) {
      return FALSE;
    }
    // Skip redirection if it was explicitly disallowed.
    if ($this->isRedirectDisabled()) {
      return FALSE;
    }

    return $this->redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function setIgnoreDestination(bool $status = TRUE) {
    $this->ignoreDestination = $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIgnoreDestination(): bool {
    return $this->ignoreDestination;
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
    if ($this->isValidationComplete()) {
      throw new \LogicException('Form errors cannot be set after form validation has finished.');
    }

    $errors = $this->getErrors();
    if (!isset($errors[$name])) {
      $record = TRUE;
      $limit_validation_errors = $this->getLimitValidationErrors();
      if ($limit_validation_errors !== NULL) {
        $record = FALSE;
        foreach ($limit_validation_errors as $section) {
          // Exploding by '][' reconstructs the element's #parents. If the
          // reconstructed #parents begin with the same keys as the specified
          // section, then the element's values are within the part of
          // $form_state->getValues() that the clicked button requires to be
          // valid, so errors for this element must be recorded. As the exploded
          // array will all be strings, we need to cast every value of the
          // section array to string.
          if (array_slice(explode('][', $name), 0, count($section)) === array_map('strval', $section)) {
            $record = TRUE;
            break;
          }
        }
      }
      if ($record) {
        $errors[$name] = $message;
        $this->errors = $errors;
        static::setAnyErrors();
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setError(array &$element, $message = '') {
    $this->setErrorByName(implode('][', $element['#parents']), $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrors() {
    $this->errors = [];
    static::setAnyErrors(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getError(array $element) {
    if ($errors = $this->getErrors()) {
      $parents = [];
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
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuild($rebuild = TRUE) {
    $this->rebuild = $rebuild;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRebuilding() {
    return $this->rebuild;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCallback($callback) {
    if (is_string($callback) && str_starts_with($callback, '::')) {
      $callback = [$this->getFormObject(), substr($callback, 2)];
    }
    return $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormObject(FormInterface $form_object) {
    $this->addBuildInfo('callback_object', $form_object);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject() {
    return $this->getBuildInfo()['callback_object'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCleanValueKeys() {
    return $this->cleanValueKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function setCleanValueKeys(array $cleanValueKeys) {
    $this->cleanValueKeys = $cleanValueKeys;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCleanValueKey($cleanValueKey) {
    $keys = $this->getCleanValueKeys();
    $this->setCleanValueKeys(array_merge((array) $keys, [$cleanValueKey]));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanValues() {
    foreach ($this->getCleanValueKeys() as $value) {
      $this->unsetValue($value);
    }

    // Remove button values.
    // \Drupal::formBuilder()->doBuildForm() collects all button elements in a
    // form. We remove the button value separately for each button element.
    foreach ($this->getButtons() as $button) {
      // Remove this button's value from the submitted form values by finding
      // the value corresponding to this button.
      // We iterate over the #parents of this button and move a reference to
      // each parent in self::getValues(). For example, if #parents is:
      // @code
      //   array('foo', 'bar', 'baz')
      // @endcode
      // Then the corresponding self::getValues() part will look like this:
      // @code
      // array(
      //   'foo' => array(
      //     'bar' => array(
      //       'baz' => 'button_value',
      //     ),
      //   ),
      // )
      // @endcode
      // We start by (re)moving 'baz' to $last_parent, so we are able unset it
      // at the end of the iteration. Initially, $values will contain a
      // reference to self::getValues(), but in the iteration we move the
      // reference to self::getValue('foo'), and finally to
      // self::getValue(array('foo', 'bar')), which is the level where we
      // can unset 'baz' (that is stored in $last_parent).
      $parents = $button['#parents'];
      $last_parent = array_pop($parents);
      $key_exists = NULL;
      $values = &NestedArray::getValue($this->getValues(), $parents, $key_exists);
      if ($key_exists && is_array($values)) {
        unset($values[$last_parent]);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setInvalidToken($invalid_token) {
    $this->invalidToken = (bool) $invalid_token;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInvalidToken() {
    return $this->invalidToken;
  }

  /**
   * Wraps ModuleHandler::loadInclude().
   */
  protected function moduleLoadInclude($module, $type, $name = NULL) {
    return \Drupal::moduleHandler()->loadInclude($module, $type, $name);
  }

}
