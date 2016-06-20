<?php

namespace Drupal\Core\Form;

/**
 * Provides an interface for form building and processing.
 */
interface FormBuilderInterface {

  /**
   * Request key for AJAX forms that submit to the form's original route.
   *
   * This constant is distinct from a "drupal_ajax" value for
   * \Drupal\Core\EventSubscriber\MainContentViewSubscriber::WRAPPER_FORMAT,
   * because that one is set for all AJAX submissions, including ones with
   * dedicated routes for which self::buildForm() should not exit early via a
   * \Drupal\Core\Form\FormAjaxException.
   *
   * @todo Re-evaluate the need for this constant after
   *   https://www.drupal.org/node/2502785 and
   *   https://www.drupal.org/node/2503429.
   */
  const AJAX_FORM_REQUEST = 'ajax_form';

  /**
   * Determines the ID of a form.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_arg
   *   The value is identical to that of self::getForm()'s $form_arg argument.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The unique string identifying the desired form.
   */
  public function getFormId($form_arg, FormStateInterface &$form_state);

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
   * @param ...
   *   Any additional arguments are passed on to the functions called by
   *   \Drupal::formBuilder()->getForm(), including the unique form constructor
   *   function. For example, the node_edit form requires that a node object is
   *   passed in here when it is called. These are available to implementations
   *   of hook_form_alter() and hook_form_FORM_ID_alter() as the array
   *   $form_state->getBuildInfo()['args'].
   *
   * @return array
   *   The form array.
   *
   * @see \Drupal\Core\Form\FormBuilderInterface::buildForm()
   */
  public function getForm($form_arg);

  /**
   * Builds and processes a form for a given form ID.
   *
   * The form may also be retrieved from the cache if the form was built in a
   * previous page load. The form is then passed on for processing, validation,
   * and submission if there is proper input.
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_id
   *   The value must be one of the following:
   *   - The name of a class that implements \Drupal\Core\Form\FormInterface.
   *   - An instance of a class that implements \Drupal\Core\Form\FormInterface.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The rendered form. This function may also perform a redirect and hence
   *   may not return at all depending upon the $form_state flags that were set.
   *
   * @throws \Drupal\Core\Form\FormAjaxException
   *   Thrown when a form is triggered via an AJAX submission. It will be
   *   handled by \Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber.
   * @throws \Drupal\Core\Form\EnforcedResponseException
   *   Thrown when a form builder returns a response directly, usually a
   *   \Symfony\Component\HttpFoundation\RedirectResponse. It will be handled by
   *   \Drupal\Core\EventSubscriber\EnforcedFormResponseSubscriber.
   *
   * @see self::redirectForm()
   */
  public function buildForm($form_id, FormStateInterface &$form_state);

  /**
   * Constructs a new $form from the information in $form_state.
   *
   * This is the key function for making multi-step forms advance from step to
   * step. It is called by self::processForm() when all user input processing,
   * including calling validation and submission handlers, for the request is
   * finished. If a validate or submit handler set $form_state->isRebuilding()
   * to TRUE, and if other conditions don't preempt a rebuild from happening,
   * then this function is called to generate a new $form, the next step in the
   * form workflow, to be returned for rendering.
   *
   * Ajax form submissions are almost always multi-step workflows, so that is
   * one common use-case during which form rebuilding occurs.
   *
   * @param string $form_id
   *   The unique string identifying the desired form. If a function with that
   *   name exists, it is called to build the form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array|null $old_form
   *   (optional) A previously built $form. Used to retain the #build_id and
   *   #action properties in Ajax callbacks and similar partial form rebuilds.
   *   The only properties copied from $old_form are the ones which both exist
   *   in $old_form and for which $form_state->getRebuildInfo()['copy'][PROPERTY]
   *   is TRUE. If $old_form is not passed, the entire $form is rebuilt freshly.
   *   'rebuild_info' needs to be a separate top-level property next to
   *   'build_info', since the contained data must not be cached.
   *
   * @return array
   *   The newly built form.
   *
   * @see self::processForm()
   */
  public function rebuildForm($form_id, FormStateInterface &$form_state, $old_form = NULL);

  /**
   * Retrieves, populates, and processes a form.
   *
   * This function allows you to supply values for form elements and submit a
   * form for processing. Compare to self::getForm(), which also builds and
   * processes a form, but does not allow you to supply values.
   *
   * There is no return value, but you can check to see if there are errors
   * by calling $form_state->getErrors().
   *
   * @param \Drupal\Core\Form\FormInterface|string $form_arg
   *   The value must be one of the following:
   *   - The name of a class that implements \Drupal\Core\Form\FormInterface.
   *   - An instance of a class that implements \Drupal\Core\Form\FormInterface.
   * @param $form_state
   *   The current state of the form. Most important is the
   *   $form_state->getValues() collection, a tree of data used to simulate the
   *   incoming \Drupal::request()->request information from a user's form
   *   submission. If a key is not filled in $form_state->getValues(), then the
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
   *   function mymodule_form($form, FormStateInterface &$form_state, &$object) {
   *   }
   *   @endcode
   *   would be called via self::submitForm() as follows:
   *   @code
   *   $form_state->setValues($my_form_values);
   *   $form_state->addBuildInfo('args', [&$object]);
   *   \Drupal::formBuilder()->submitForm('mymodule_form', $form_state);
   *   @endcode
   * For example:
   * @code
   * // register a new user
   * $form_state = new FormState();
   * $values['name'] = 'robo-user';
   * $values['mail'] = 'robouser@example.com';
   * $values['pass']['pass1'] = 'password';
   * $values['pass']['pass2'] = 'password';
   * $values['op'] = t('Create new account');
   * $form_state->setValues($values);
   * \Drupal::formBuilder()->submitForm('user_register_form', $form_state);
   * @endcode
   */
  public function submitForm($form_arg, FormStateInterface &$form_state);

  /**
   * Retrieves the structured array that defines a given form.
   *
   * @param string $form_id
   *   The unique string identifying the desired form. If a function
   *   with that name exists, it is called to build the form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form, including the additional arguments to
   *   self::getForm() or self::submitForm() in the 'args' component of the
   *   array.
   *
   * @return mixed|\Symfony\Component\HttpFoundation\Response
   */
  public function retrieveForm($form_id, FormStateInterface &$form_state);

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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. This includes the current persistent
   *   storage data for the form, and any data passed along by earlier steps
   *   when displaying a multi-step form. Additional information, like the
   *   sanitized \Drupal::request()->request data, is also accumulated here.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  public function processForm($form_id, &$form, FormStateInterface &$form_state);

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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Passed in here so that hook_form_alter()
   *   calls can use it, as well.
   */
  public function prepareForm($form_id, &$form, FormStateInterface &$form_state);

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
   *   $form_state->getValues() and checking $form_state->isRebuilding(). The
   *   form building functions must then be implemented to use the $form_state
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. In this context, it is used to accumulate
   *   information about which button was clicked when the form was submitted,
   *   as well as the sanitized \Drupal::request()->request data.
   *
   * @return array
   */
  public function doBuildForm($form_id, &$element, FormStateInterface &$form_state);

}
