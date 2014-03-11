<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormBuilderInterface.
 */

namespace Drupal\Core\Form;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface for form building and processing.
 */
interface FormBuilderInterface extends FormErrorInterface {

  /**
   * Determines the ID of a form.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_arg
   *   The value is identical to that of self::getForm()'s $form_arg argument.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return string
   *   The unique string identifying the desired form.
   */
  public function getFormId($form_arg, &$form_state);

  /**
   * Gets a renderable form array.
   *
   * This function should be used instead of self::buildForm() when $form_state
   * is not needed (i.e., when initially rendering the form) and is often
   * used as a menu callback.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_arg
   *   The value must be one of the following:
   *   - The name of a class that implements \Drupal\Core\Form\FormInterface.
   *   - An instance of a class that implements \Drupal\Core\Form\FormInterface.
   *   - The name of a function that builds the form.
   * @param ...
   *   Any additional arguments are passed on to the functions called by
   *   drupal_get_form(), including the unique form constructor function. For
   *   example, the node_edit form requires that a node object is passed in here
   *   when it is called. These are available to implementations of
   *   hook_form_alter() and hook_form_FORM_ID_alter() as the array
   *   $form_state['build_info']['args'].
   *
   * @return array
   *   The form array.
   *
   * @see drupal_build_form()
   */
  public function getForm($form_arg);

  /**
   * Builds and processes a form for a given form ID.
   *
   * The form may also be retrieved from the cache if the form was built in a
   * previous page-load. The form is then passed on for processing, validation
   * and submission if there is proper input.
   *
   * @param $form_id
   *   The unique string identifying the desired form.
   * @param array $form_state
   *   An array which stores information about the form. This is passed as a
   *   reference so that the caller can use it to examine what in the form
   *   changed when the form submission process is complete. Furthermore, it may
   *   be used to store information related to the processed data in the form,
   *   which will persist across page requests when the 'cache' or 'rebuild'
   *   flag is set. The following parameters may be set in $form_state to affect
   *   how the form is rendered:
   *   - build_info: Internal. An associative array of information stored by
   *     Form API that is necessary to build and rebuild the form from cache
   *     when the original context may no longer be available:
   *     - callback: The actual callback to be used to retrieve the form array.
   *       Can be any callable. If none is provided $form_id is used as the name
   *       of a function to call instead.
   *     - args: A list of arguments to pass to the form constructor.
   *     - files: An optional array defining include files that need to be
   *       loaded for building the form. Each array entry may be the path to a
   *       file or another array containing values for the parameters 'type',
   *       'module' and 'name' as needed by module_load_include(). The files
   *       listed here are automatically loaded by form_get_cache(). By default
   *       the current menu router item's 'file' definition is added, if any.
   *       Use form_load_include() to add include files from a form constructor.
   *     - form_id: Identification of the primary form being constructed and
   *       processed.
   *     - base_form_id: Identification for a base form, as declared in the form
  *       class's \Drupal\Core\Form\BaseFormIdInterface::getBaseFormId() method.
   *   - rebuild_info: Internal. Similar to 'build_info', but pertaining to
   *     self::rebuildForm().
   *   - rebuild: Normally, after the entire form processing is completed and
   *     submit handlers have run, a form is considered to be done and
   *     self::redirectForm() will redirect the user to a new page using a GET
   *     request (so a browser refresh does not re-submit the form). However, if
   *     'rebuild' has been set to TRUE, then a new copy of the form is
   *     immediately built and sent to the browser, instead of a redirect. This
   *     is used for multi-step forms, such as wizards and confirmation forms.
   *     Normally, $form_state['rebuild'] is set by a submit handler, since its
   *     is usually logic within a submit handler that determines whether a form
   *     is done or requires another step. However, a validation handler may
   *     already set $form_state['rebuild'] to cause the form processing to
   *     bypass submit handlers and rebuild the form instead, even if there are
   *     no validation errors.
   *   - response: Used when a form needs to return some kind of a
   *     \Symfony\Component\HttpFoundation\Response object, e.g., a
   *     \Symfony\Component\HttpFoundation\BinaryFileResponse when triggering a
   *     file download. If you use the $form_state['redirect'] key, it will be
   *     used to build a \Symfony\Component\HttpFoundation\RedirectResponse and
   *     will populate this key.
   *   - redirect: Used to redirect the form on submission. It may either be a
   *     string containing the destination URL, or an array of arguments
   *     compatible with url(). See url() for complete information.
   *   - no_redirect: If set to TRUE the form will NOT perform a redirect,
   *     even if 'redirect' is set.
   *   - method: The HTTP form method to use for finding the input for this
   *     form. May be 'post' or 'get'. Defaults to 'post'. Note that 'get'
   *     method forms do not use form ids so are always considered to be
   *     submitted, which can have unexpected effects. The 'get' method should
   *     only be used on forms that do not change data, as that is exclusively
   *     the domain of 'post.'
   *   - cache: If set to TRUE the original, unprocessed form structure will be
   *     cached, which allows the entire form to be rebuilt from cache. A
   *     typical form workflow involves two page requests; first, a form is
   *     built and rendered for the user to fill in. Then, the user fills the
   *     form in and submits it, triggering a second page request in which the
   *     form must be built and processed. By default, $form and $form_state are
   *     built from scratch during each of these page requests. Often, it is
   *     necessary or desired to persist the $form and $form_state variables
   *     from the initial page request to the one that processes the submission.
   *     'cache' can be set to TRUE to do this. A prominent example is an
   *     Ajax-enabled form, in which ajax_process_form() enables form caching
   *     for all forms that include an element with the #ajax property. (The
   *     Ajax handler has no way to build the form itself, so must rely on the
   *     cached version.) Note that the persistence of $form and $form_state
   *     happens automatically for (multi-step) forms having the 'rebuild' flag
   *     set, regardless of the value for 'cache'.
   *   - no_cache: If set to TRUE the form will NOT be cached, even if 'cache'
   *     is set.
   *   - values: An associative array of values submitted to the form. The
   *     validation functions and submit functions use this array for nearly all
   *     their decision making. (Note that #tree determines whether the values
   *     are a flat array or an array whose structure parallels the $form array.
   *     See the @link forms_api_reference.html Form API reference @endlink for
   *     more information.)
   *   - input: The array of values as they were submitted by the user. These
   *     are raw and unvalidated, so should not be used without a thorough
   *     understanding of security implications. In almost all cases, code
   *     should use the data in the 'values' array exclusively. The most common
   *     use of this key is for multi-step forms that need to clear some of the
   *     user input when setting 'rebuild'. The values correspond to
   *     \Drupal::request()->request or \Drupal::request()->query, depending on
   *     the 'method' chosen.
   *   - always_process: If TRUE and the method is GET, a form_id is not
   *     necessary. This should only be used on RESTful GET forms that do NOT
   *     write data, as this could lead to security issues. It is useful so that
   *     searches do not need to have a form_id in their query arguments to
   *     trigger the search.
   *   - must_validate: Ordinarily, a form is only validated once, but there are
   *     times when a form is resubmitted internally and should be validated
   *     again. Setting this to TRUE will force that to happen. This is most
   *     likely to occur during Ajax operations.
   *   - programmed: If TRUE, the form was submitted programmatically, usually
   *     invoked via self::submitForm(). Defaults to FALSE.
   *   - process_input: Boolean flag. TRUE signifies correct form submission.
   *     This is always TRUE for programmed forms coming from self::submitForm()
   *     (see 'programmed' key), or if the form_id coming from the
   *     \Drupal::request()->request data is set and matches the current form_id.
   *   - submitted: If TRUE, the form has been submitted. Defaults to FALSE.
   *   - executed: If TRUE, the form was submitted and has been processed and
   *     executed. Defaults to FALSE.
   *   - triggering_element: (read-only) The form element that triggered
   *     submission, which may or may not be a button (in the case of Ajax
   *     forms). This key is often used to distinguish between various buttons
   *     in a submit handler, and is also used in Ajax handlers.
   *   - has_file_element: Internal. If TRUE, there is a file element and Form
   *     API will set the appropriate 'enctype' HTML attribute on the form.
   *   - groups: Internal. An array containing references to details elements to
   *     render them within vertical tabs.
   *   - storage: $form_state['storage'] is not a special key, and no specific
   *     support is provided for it in the Form API. By tradition it was
   *     the location where application-specific data was stored for
   *     communication between the submit, validation, and form builder
   *     functions, especially in a multi-step-style form. Form implementations
   *     may use any key(s) within $form_state (other than the keys listed here
   *     and other reserved ones used by Form API internals) for this kind of
   *     storage. The recommended way to ensure that the chosen key doesn't
   *     conflict with ones used by the Form API or other modules is to use the
   *     module name as the key name or a prefix for the key name. For example,
   *     the entity form controller classes use $this->entity in entity forms,
   *     or $form_state['controller']->getEntity() outside the controller, to
   *     store information about the entity being edited, and this information
   *     stays available across successive clicks of the "Preview" button (if
   *     available) as well as when the "Save" button is finally clicked.
   *   - buttons: A list containing copies of all submit and button elements in
   *     the form.
   *   - complete_form: A reference to the $form variable containing the
   *     complete form structure. #process, #after_build, #element_validate, and
   *     other handlers being invoked on a form element may use this reference
   *     to access other information in the form the element is contained in.
   *   - temporary: An array holding temporary data accessible during the
   *     current page request only. All $form_state properties that are not
   *     reserved keys (see form_state_keys_no_cache()) persist throughout a
   *     multistep form sequence. Form API provides this key for modules to
   *     communicate information across form-related functions during a single
   *     page request. It may be used to temporarily save data that does not
   *     need to or should not be cached during the whole form workflow; e.g.,
   *     data that needs to be accessed during the current form build process
   *     only. There is no use-case for this functionality in Drupal core.
   *   Information on how certain $form_state properties control redirection
   *   behavior after form submission may be found in self::redirectForm().
   *
   * @return array
   *   The rendered form. This function may also perform a redirect and hence
   *   may not return at all depending upon the $form_state flags that were set.
   *
   * @see self::redirectForm()
   */
  public function buildForm($form_id, array &$form_state);

  /**
   * Retrieves default values for the $form_state array.
   */
  public function getFormStateDefaults();

  /**
   * Constructs a new $form from the information in $form_state.
   *
   * This is the key function for making multi-step forms advance from step to
   * step. It is called by self::processForm() when all user input processing,
   * including calling validation and submission handlers, for the request is
   * finished. If a validate or submit handler set $form_state['rebuild'] to
   * TRUE, and if other conditions don't preempt a rebuild from happening, then
   * this function is called to generate a new $form, the next step in the form
   * workflow, to be returned for rendering.
   *
   * Ajax form submissions are almost always multi-step workflows, so that is
   * one common use-case during which form rebuilding occurs. See
   * Drupal\system\FormAjaxController::content() for more information about
   * creating Ajax-enabled forms.
   *
   * @param string $form_id
   *   The unique string identifying the desired form. If a function with that
   *   name exists, it is called to build the form array.
   * @param array $form_state
   *   A keyed array containing the current state of the form.
   * @param array|null $old_form
   *   (optional) A previously built $form. Used to retain the #build_id and
   *   #action properties in Ajax callbacks and similar partial form rebuilds.
   *   The only properties copied from $old_form are the ones which both exist
   *   in $old_form and for which $form_state['rebuild_info']['copy'][PROPERTY]
   *   is TRUE. If $old_form is not passed, the entire $form is rebuilt freshly.
   *   'rebuild_info' needs to be a separate top-level property next to
   *   'build_info', since the contained data must not be cached.
   *
   * @return array
   *   The newly built form.
   *
   * @see self::processForm()
   * @see \Drupal\system\FormAjaxController::content()
   */
  public function rebuildForm($form_id, &$form_state, $old_form = NULL);

  /**
   * Fetches a form from the cache.
   */
  public function getCache($form_build_id, &$form_state);

  /**
   * Stores a form in the cache.
   */
  public function setCache($form_build_id, $form, $form_state);

  /**
   * Retrieves, populates, and processes a form.
   *
   * This function allows you to supply values for form elements and submit a
   * form for processing. Compare to self::getForm(), which also builds and
   * processes a form, but does not allow you to supply values.
   *
   * There is no return value, but you can check to see if there are errors
   * by calling form_get_errors().
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_arg
   *   A form object to use to build the form, or the unique string identifying
   *   the desired form. If $form_arg is a string and a function with that
   *   name exists, it is called to build the form array.
   * @param $form_state
   *   A keyed array containing the current state of the form. Most important is
   *   the $form_state['values'] collection, a tree of data used to simulate the
   *   incoming \Drupal::request()->request information from a user's form
   *   submission. If a key is not filled in $form_state['values'], then the
   *   default value of the respective element is used. To submit an unchecked
   *   checkbox or other control that browsers submit by not having a
   *   \Drupal::request()->request entry, include the key, but set the value to
   *   NULL.
   * @param ...
   *   Any additional arguments are passed on to the functions called by
   *   self::submitForm(), including the unique form constructor function.
   *   For example, the node_edit form requires that a node object be passed
   *   in here when it is called. Arguments that need to be passed by reference
   *   should not be included here, but rather placed directly in the
   *   $form_state build info array so that the reference can be preserved. For
   *   example, a form builder function with the following signature:
   *   @code
   *   function mymodule_form($form, &$form_state, &$object) {
   *   }
   *   @endcode
   *   would be called via self::submitForm() as follows:
   *   @code
   *   $form_state['values'] = $my_form_values;
   *   $form_state['build_info']['args'] = array(&$object);
   *   drupal_form_submit('mymodule_form', $form_state);
   *   @endcode
   * For example:
   * @code
   * // register a new user
   * $form_state = array();
   * $form_state['values']['name'] = 'robo-user';
   * $form_state['values']['mail'] = 'robouser@example.com';
   * $form_state['values']['pass']['pass1'] = 'password';
   * $form_state['values']['pass']['pass2'] = 'password';
   * $form_state['values']['op'] = t('Create new account');
   * drupal_form_submit('user_register_form', $form_state);
   * @endcode
   */
  public function submitForm($form_arg, &$form_state);

  /**
   * Retrieves the structured array that defines a given form.
   *
   * @param string $form_id
   *   The unique string identifying the desired form. If a function
   *   with that name exists, it is called to build the form array.
   * @param array $form_state
   *   A keyed array containing the current state of the form, including the
   *   additional arguments to self::getForm() or self::submitForm() in the
   *   'args' component of the array.
   *
   * @return mixed|\Symfony\Component\HttpFoundation\Response
   */
  public function retrieveForm($form_id, &$form_state);

  /**
   * Processes a form submission.
   *
   * This function is the heart of form API. The form gets built, validated and
   * in appropriate cases, submitted and rebuilt.
   *
   * @param string $form_id
   *   The unique string identifying the current form.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A keyed array containing the current state of the form. This
   *   includes the current persistent storage data for the form, and
   *   any data passed along by earlier steps when displaying a
   *   multi-step form. Additional information, like the sanitized
   *   \Drupal::request()->request data, is also accumulated here.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  public function processForm($form_id, &$form, &$form_state);

  /**
   * Prepares a structured form array.
   *
   * Adds required elements, executes any hook_form_alter functions, and
   * optionally inserts a validation token to prevent tampering.
   *
   * @param string $form_id
   *   A unique string identifying the form for validation, submission,
   *   theming, and hook_form_alter functions.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   A keyed array containing the current state of the form. Passed
   *   in here so that hook_form_alter() calls can use it, as well.
   */
  public function prepareForm($form_id, &$form, &$form_state);

  /**
   * Validates user-submitted form data in the $form_state array.
   *
   * @param $form_id
   *   A unique string identifying the form for validation, submission,
   *   theming, and hook_form_alter functions.
   * @param $form
   *   An associative array containing the structure of the form, which is
   *   passed by reference. Form validation handlers are able to alter the form
   *   structure (like #process and #after_build callbacks during form building)
   *   in case of a validation error. If a validation handler alters the form
   *   structure, it is responsible for validating the values of changed form
   *   elements in $form_state['values'] to prevent form submit handlers from
   *   receiving unvalidated values.
   * @param $form_state
   *   A keyed array containing the current state of the form. The current
   *   user-submitted data is stored in $form_state['values'], though
   *   form validation functions are passed an explicit copy of the
   *   values for the sake of simplicity. Validation handlers can also use
   *   $form_state to pass information on to submit handlers. For example:
   *     $form_state['data_for_submission'] = $data;
   *   This technique is useful when validation requires file parsing,
   *   web service requests, or other expensive requests that should
   *   not be repeated in the submission step.
   */
  public function validateForm($form_id, &$form, &$form_state);

  /**
   * Redirects the user to a URL after a form has been processed.
   *
   * After a form is submitted and processed, normally the user should be
   * redirected to a new destination page. This function figures out what that
   * destination should be, based on the $form_state array and the 'destination'
   * query string in the request URL, and redirects the user there.
   *
   * Usually (for exceptions, see below) $form_state['redirect'] determines
   * where to redirect the user. This can be set either to a string (the path to
   * redirect to), or an array of arguments for url(). If
   * $form_state['redirect'] is missing, the user is usually (again, see below
   * for exceptions) redirected back to the page they came from, where they
   * should see a fresh, unpopulated copy of the form.
   *
   * Here is an example of how to set up a form to redirect to the path 'node':
   * @code
   * $form_state['redirect'] = 'node';
   * @endcode
   * And here is an example of how to redirect to 'node/123?foo=bar#baz':
   * @code
   * $form_state['redirect'] = array(
   *   'node/123',
   *   array(
   *     'query' => array(
   *       'foo' => 'bar',
   *     ),
   *     'fragment' => 'baz',
   *   ),
   * );
   * @endcode
   *
   * There are several exceptions to the "usual" behavior described above:
   * - If $form_state['programmed'] is TRUE, the form submission was usually
   *   invoked via self::submitForm(), so any redirection would break the script
   *   that invoked self::submitForm() and no redirection is done.
   * - If $form_state['rebuild'] is TRUE, the form is being rebuilt, and no
   *   redirection is done.
   * - If $form_state['no_redirect'] is TRUE, redirection is disabled. This is
   *   set, for instance, by \Drupal\system\FormAjaxController::getForm() to
   *   prevent redirection in Ajax callbacks. $form_state['no_redirect'] should
   *   never be set or altered by form builder functions or form validation
   *   or submit handlers.
   * - If $form_state['redirect'] is set to FALSE, redirection is disabled.
   * - If none of the above conditions has prevented redirection, then the
   *   redirect is accomplished by returning a RedirectResponse, passing in the
   *   value of $form_state['redirect'] if it is set, or the current path if it
   *   is not. RedirectResponse preferentially uses the value of
   *   \Drupal::request->query->get('destination') (the 'destination' URL query
   *   string) if it is present, so this will override any values set by
   *   $form_state['redirect'].
   *
   * @param $form_state
   *   An associative array containing the current state of the form.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *
   * @see self::processForm()
   * @see self::buildForm()
   */
  public function redirectForm($form_state);

  /**
   * Executes custom validation and submission handlers for a given form.
   *
   * Button-specific handlers are checked first. If none exist, the function
   * falls back to form-level handlers.
   *
   * @param $type
   *   The type of handler to execute. 'validate' or 'submit' are the
   *   defaults used by Form API.
   * @param $form
   *   An associative array containing the structure of the form.
   * @param $form_state
   *   A keyed array containing the current state of the form. If the user
   *   submitted the form by clicking a button with custom handler functions
   *   defined, those handlers will be stored here.
   */
  public function executeHandlers($type, &$form, &$form_state);

  /**
   * Builds and processes all elements in the structured form array.
   *
   * Adds any required properties to each element, maps the incoming input data
   * to the proper elements, and executes any #process handlers attached to a
   * specific element.
   *
   * This is one of the three primary functions that recursively iterates a form
   * array. This one does it for completing the form building process. The other
   * two are self::doValidateForm() (invoked via self::validateForm() and used
   * to invoke validation logic for each element) and drupal_render() (for
   * rendering each element). Each of these three pipelines provides ample
   * opportunity for modules to customize what happens. For example, during this
   * function's life cycle, the following functions get called for each element:
   * - $element['#value_callback']: A callable that implements how user input is
   *   mapped to an element's #value property. This defaults to a function named
   *   'form_type_TYPE_value' where TYPE is $element['#type'].
   * - $element['#process']: An array of functions called after user input has
   *   been mapped to the element's #value property. These functions can be used
   *   to dynamically add child elements: for example, for the 'date' element
   *   type, one of the functions in this array is form_process_datetime(),
   *   which adds the individual 'date', and 'time'. child elements. These
   *   functions can also be used to set additional properties or implement
   *   special logic other than adding child elements: for example, for the
   *   'details' element type, one of the functions in this array is
   *   form_process_details(), which adds the attributes and JavaScript needed
   *   to make the details work in older browsers. The #process functions are
   *   called in preorder traversal, meaning they are called for the parent
   *   element first, then for the child elements.
   * - $element['#after_build']: An array of callables called after
   *   self::doBuildForm() is done with its processing of the element. These are
   *   called in postorder traversal, meaning they are called for the child
   *   elements first, then for the parent element.
   * There are similar properties containing callback functions invoked by
   * self::doValidateForm() and drupal_render(), appropriate for those
   * operations.
   *
   * Developers are strongly encouraged to integrate the functionality needed by
   * their form or module within one of these three pipelines, using the
   * appropriate callback property, rather than implementing their own recursive
   * traversal of a form array. This facilitates proper integration between
   * multiple modules. For example, module developers are familiar with the
   * relative order in which hook_form_alter() implementations and #process
   * functions run. A custom traversal function that affects the building of a
   * form is likely to not integrate with hook_form_alter() and #process in the
   * expected way. Also, deep recursion within PHP is both slow and memory
   * intensive, so it is best to minimize how often it's done.
   *
   * As stated above, each element's #process functions are executed after its
   * #value has been set. This enables those functions to execute conditional
   * logic based on the current value. However, all of self::doBuildForm() runs
   * before self::validateForm() is called, so during #process function
   * execution, the element's #value has not yet been validated, so any code
   * that requires validated values must reside within a submit handler.
   *
   * As a security measure, user input is used for an element's #value only if
   * the element exists within $form, is not disabled (as per the #disabled
   * property), and can be accessed (as per the #access property, except that
   * forms submitted using self::submitForm() bypass #access restrictions). When
   * user input is ignored due to #disabled and #access restrictions, the
   * element's default value is used.
   *
   * Because of the preorder traversal, where #process functions of an element
   * run before user input for its child elements is processed, and because of
   * the Form API security of user input processing with respect to #access and
   * #disabled described above, this generally means that #process functions
   * should not use an element's (unvalidated) #value to affect the #disabled or
   * #access of child elements. Use-cases where a developer may be tempted to
   * implement such conditional logic usually fall into one of two categories:
   * - Where user input from the current submission must affect the structure of
   *   a form, including properties like #access and #disabled that affect how
   *   the next submission needs to be processed, a multi-step workflow is
   *   needed. This is most commonly implemented with a submit handler setting
   *   persistent data within $form_state based on *validated* values in
   *   $form_state['values'] and setting $form_state['rebuild']. The form
   *   building functions must then be implemented to use the $form_state data
   *   to rebuild the form with the structure appropriate for the new state.
   * - Where user input must affect the rendering of the form without affecting
   *   its structure, the necessary conditional rendering logic should reside
   *   within functions that run during the rendering phase (#pre_render,
   *   #theme, #theme_wrappers, and #post_render).
   *
   * @param string $form_id
   *   A unique string identifying the form for validation, submission,
   *   theming, and hook_form_alter functions.
   * @param array $element
   *   An associative array containing the structure of the current element.
   * @param array $form_state
   *   A keyed array containing the current state of the form. In this
   *   context, it is used to accumulate information about which button
   *   was clicked when the form was submitted, as well as the sanitized
   *   \Drupal::request()->request data.
   *
   * @return array
   */
  public function doBuildForm($form_id, &$element, &$form_state);

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
   * @param $element
   *   The form element that should have its value updated; in most cases you
   *   can just pass in the element from the $form array, although the only
   *   component that is actually used is '#parents'. If constructing yourself,
   *   set $element['#parents'] to be an array giving the path through the form
   *   array's keys to the element whose value you want to update. For instance,
   *   if you want to update the value of $form['elem1']['elem2'], which should
   *   be stored in $form_state['values']['elem1']['elem2'], you would set
   *   $element['#parents'] = array('elem1','elem2').
   * @param $value
   *   The new value for the form element.
   * @param $form_state
   *   Form state array where the value change should be recorded.
   */
  public function setValue($element, $value, &$form_state);

  /**
   * Allows PHP array processing of multiple select options with the same value.
   *
   * Used for form select elements which need to validate HTML option groups
   * and multiple options which may return the same value. Associative PHP
   * arrays cannot handle these structures, since they share a common key.
   *
   * @param array $array
   *   The form options array to process.
   *
   * @return array
   *   An array with all hierarchical elements flattened to a single array.
   */
  public function flattenOptions(array $array);

  /**
   * Sets the request object to use.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request);

}
