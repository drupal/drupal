<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormBuilder.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\Exception\BrokenPostRequestException;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides form building and processing.
 *
 * @ingroup form_api
 */
class FormBuilder implements FormBuilderInterface, FormValidatorInterface, FormSubmitterInterface, FormCacheInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var \Drupal\Core\Form\FormValidatorInterface
   */
  protected $formValidator;

  /**
   * @var \Drupal\Core\Form\FormSubmitterInterface
   */
  protected $formSubmitter;

  /**
   * The form cache.
   *
   * @var \Drupal\Core\Form\FormCacheInterface
   */
  protected $formCache;

  /**
   * Constructs a new FormBuilder.
   *
   * @param \Drupal\Core\Form\FormValidatorInterface $form_validator
   *   The form validator.
   * @param \Drupal\Core\Form\FormSubmitterInterface $form_submitter
   *   The form submission processor.
   * @param \Drupal\Core\Form\FormCacheInterface $form_cache
   *   The form cache.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(FormValidatorInterface $form_validator, FormSubmitterInterface $form_submitter, FormCacheInterface $form_cache, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, RequestStack $request_stack, ClassResolverInterface $class_resolver, ElementInfoManagerInterface $element_info, ThemeManagerInterface $theme_manager, CsrfTokenGenerator $csrf_token = NULL) {
    $this->formValidator = $form_validator;
    $this->formSubmitter = $form_submitter;
    $this->formCache = $form_cache;
    $this->moduleHandler = $module_handler;
    $this->eventDispatcher = $event_dispatcher;
    $this->requestStack = $request_stack;
    $this->classResolver = $class_resolver;
    $this->elementInfo = $element_info;
    $this->csrfToken = $csrf_token;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId($form_arg, FormStateInterface &$form_state) {
    // If the $form_arg is the name of a class, instantiate it. Don't allow
    // arbitrary strings to be passed to the class resolver.
    if (is_string($form_arg) && class_exists($form_arg)) {
      $form_arg = $this->classResolver->getInstanceFromDefinition($form_arg);
    }

    if (!is_object($form_arg) || !($form_arg instanceof FormInterface)) {
      throw new \InvalidArgumentException("The form argument $form_arg is not a valid form.");
    }

    // Add the $form_arg as the callback object and determine the form ID.
    $form_state->setFormObject($form_arg);
    if ($form_arg instanceof BaseFormIdInterface) {
      $form_state->addBuildInfo('base_form_id', $form_arg->getBaseFormId());
    }
    return $form_arg->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm($form_arg) {
    $form_state = new FormState();

    $args = func_get_args();
    // Remove $form_arg from the arguments.
    unset($args[0]);
    $form_state->addBuildInfo('args', array_values($args));

    return $this->buildForm($form_arg, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm($form_id, FormStateInterface &$form_state) {
    // Ensure the form ID is prepared.
    $form_id = $this->getFormId($form_id, $form_state);

    $request = $this->requestStack->getCurrentRequest();

    // Inform $form_state about the request method that's building it, so that
    // it can prevent persisting state changes during HTTP methods for which
    // that is disallowed by HTTP: GET and HEAD.
    $form_state->setRequestMethod($request->getMethod());

    // Initialize the form's user input. The user input should include only the
    // input meant to be treated as part of what is submitted to the form, so
    // we base it on the form's method rather than the request's method. For
    // example, when someone does a GET request for
    // /node/add/article?destination=foo, which is a form that expects its
    // submission method to be POST, the user input during the GET request
    // should be initialized to empty rather than to ['destination' => 'foo'].
    $input = $form_state->getUserInput();
    if (!isset($input)) {
      $input = $form_state->isMethodType('get') ? $request->query->all() : $request->request->all();
      $form_state->setUserInput($input);
    }

    if (isset($_SESSION['batch_form_state'])) {
      // We've been redirected here after a batch processing. The form has
      // already been processed, but needs to be rebuilt. See _batch_finished().
      $form_state = $_SESSION['batch_form_state'];
      unset($_SESSION['batch_form_state']);
      return $this->rebuildForm($form_id, $form_state);
    }

    // If the incoming input contains a form_build_id, we'll check the cache for
    // a copy of the form in question. If it's there, we don't have to rebuild
    // the form to proceed. In addition, if there is stored form_state data from
    // a previous step, we'll retrieve it so it can be passed on to the form
    // processing code.
    $check_cache = isset($input['form_id']) && $input['form_id'] == $form_id && !empty($input['form_build_id']);
    if ($check_cache) {
      $form = $this->getCache($input['form_build_id'], $form_state);
    }

    // If the previous bit of code didn't result in a populated $form object, we
    // are hitting the form for the first time and we need to build it from
    // scratch.
    if (!isset($form)) {
      // If we attempted to serve the form from cache, uncacheable $form_state
      // keys need to be removed after retrieving and preparing the form, except
      // any that were already set prior to retrieving the form.
      if ($check_cache) {
        $form_state_before_retrieval = clone $form_state;
      }

      $form = $this->retrieveForm($form_id, $form_state);
      $this->prepareForm($form_id, $form, $form_state);

      // self::setCache() removes uncacheable $form_state keys (see properties
      // in \Drupal\Core\Form\FormState) in order for multi-step forms to work
      // properly. This means that form processing logic for single-step forms
      // using $form_state->isCached() may depend on data stored in those keys
      // during self::retrieveForm()/self::prepareForm(), but form processing
      // should not depend on whether the form is cached or not, so $form_state
      // is adjusted to match what it would be after a
      // self::setCache()/self::getCache() sequence. These exceptions are
      // allowed to survive here:
      // - always_process: Does not make sense in conjunction with form caching
      //   in the first place, since passing form_build_id as a GET parameter is
      //   not desired.
      // - temporary: Any assigned data is expected to survives within the same
      //   page request.
      if ($check_cache) {
        $cache_form_state = $form_state->getCacheableArray();
        $cache_form_state['always_process'] = $form_state->getAlwaysProcess();
        $cache_form_state['temporary'] = $form_state->getTemporary();
        $form_state = $form_state_before_retrieval;
        $form_state->setFormState($cache_form_state);
      }
    }

    // If this form is an AJAX request, disable all form redirects.
    $request = $this->requestStack->getCurrentRequest();
    if ($ajax_form_request = $request->query->has(static::AJAX_FORM_REQUEST)) {
      $form_state->disableRedirect();
    }

    // Now that we have a constructed form, process it. This is where:
    // - Element #process functions get called to further refine $form.
    // - User input, if any, gets incorporated in the #value property of the
    //   corresponding elements and into $form_state->getValues().
    // - Validation and submission handlers are called.
    // - If this submission is part of a multistep workflow, the form is rebuilt
    //   to contain the information of the next step.
    // - If necessary, the form and form state are cached or re-cached, so that
    //   appropriate information persists to the next page request.
    // All of the handlers in the pipeline receive $form_state by reference and
    // can use it to know or update information about the state of the form.
    $response = $this->processForm($form_id, $form, $form_state);

    // In case the post request exceeds the configured allowed size
    // (post_max_size), the post request is potentially broken. Add some
    // protection against that and at the same time have a nice error message.
    if ($ajax_form_request && !isset($form_state->getUserInput()['form_id'])) {
      throw new BrokenPostRequestException($this->getFileUploadMaxSize());
    }

    // After processing the form, if this is an AJAX form request, interrupt
    // form rendering and return by throwing an exception that contains the
    // processed form and form state. This exception will be caught by
    // \Drupal\Core\Form\EventSubscriber\FormAjaxSubscriber::onException() and
    // then passed through
    // \Drupal\Core\Form\FormAjaxResponseBuilderInterface::buildResponse() to
    // build a proper AJAX response.
    if ($ajax_form_request && $form_state->isProcessingInput()) {
      throw new FormAjaxException($form, $form_state);
    }

    // If the form returns a response, skip subsequent page construction by
    // throwing an exception.
    // @see Drupal\Core\EventSubscriber\EnforcedFormResponseSubscriber
    //
    // @todo Exceptions should not be used for code flow control. However, the
    //   Form API does not integrate with the HTTP Kernel based architecture of
    //   Drupal 8. In order to resolve this issue properly it is necessary to
    //   completely separate form submission from rendering.
    //   @see https://www.drupal.org/node/2367555
    if ($response instanceof Response) {
      throw new EnforcedResponseException($response);
    }

    // If this was a successful submission of a single-step form or the last
    // step of a multi-step form, then self::processForm() issued a redirect to
    // another page, or back to this page, but as a new request. Therefore, if
    // we're here, it means that this is either a form being viewed initially
    // before any user input, or there was a validation error requiring the form
    // to be re-displayed, or we're in a multi-step workflow and need to display
    // the form's next step. In any case, we have what we need in $form, and can
    // return it for rendering.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildForm($form_id, FormStateInterface &$form_state, $old_form = NULL) {
    $form = $this->retrieveForm($form_id, $form_state);

    // Only GET and POST are valid form methods. If the form receives its input
    // via POST, then $form_state must be persisted when it is rebuilt between
    // submissions. If the form receives its input via GET, then persisting
    // state is forbidden by $form_state->setCached(), and the form must use
    // the URL itself to transfer its state across steps. Although $form_state
    // throws an exception based on the request method rather than the form's
    // method, we base the decision to cache on the form method, because:
    // - It's the form method that defines what the form needs to do to manage
    //   its state.
    // - rebuildForm() should only be called after successful input processing,
    //   which means the request method matches the form method, and if not,
    //   there's some other error, so it's ok if an exception is thrown.
    if ($form_state->isMethodType('POST')) {
      $form_state->setCached();
    }

    // If only parts of the form will be returned to the browser (e.g., Ajax or
    // RIA clients), or if the form already had a new build ID regenerated when
    // it was retrieved from the form cache, reuse the existing #build_id.
    // Otherwise, a new #build_id is generated, to not clobber the previous
    // build's data in the form cache; also allowing the user to go back to an
    // earlier build, make changes, and re-submit.
    // @see self::prepareForm()
    $rebuild_info = $form_state->getRebuildInfo();
    $enforce_old_build_id = isset($old_form['#build_id']) && !empty($rebuild_info['copy']['#build_id']);
    $old_form_is_mutable_copy = isset($old_form['#build_id_old']);
    if ($enforce_old_build_id || $old_form_is_mutable_copy) {
      $form['#build_id'] = $old_form['#build_id'];
      if ($old_form_is_mutable_copy) {
        $form['#build_id_old'] = $old_form['#build_id_old'];
      }
    }
    else {
      if (isset($old_form['#build_id'])) {
        $form['#build_id_old'] = $old_form['#build_id'];
      }
      $form['#build_id'] = 'form-' . Crypt::randomBytesBase64();
    }

    // #action defaults to $request->getRequestUri(), but in case of Ajax and
    // other partial rebuilds, the form is submitted to an alternate URL, and
    // the original #action needs to be retained.
    if (isset($old_form['#action']) && !empty($rebuild_info['copy']['#action'])) {
      $form['#action'] = $old_form['#action'];
    }

    $this->prepareForm($form_id, $form, $form_state);

    // Caching is normally done in self::processForm(), but what needs to be
    // cached is the $form structure before it passes through
    // self::doBuildForm(), so we need to do it here.
    // @todo For Drupal 8, find a way to avoid this code duplication.
    if ($form_state->isCached()) {
      $this->setCache($form['#build_id'], $form, $form_state);
    }

    // Clear out all group associations as these might be different when
    // re-rendering the form.
    $form_state->setGroups([]);

    // Return a fully built form that is ready for rendering.
    return $this->doBuildForm($form_id, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCache($form_build_id, FormStateInterface $form_state) {
    return $this->formCache->getCache($form_build_id, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function setCache($form_build_id, $form, FormStateInterface $form_state) {
    $this->formCache->setCache($form_build_id, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCache($form_build_id) {
    $this->formCache->deleteCache($form_build_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm($form_arg, FormStateInterface &$form_state) {
    $build_info = $form_state->getBuildInfo();
    if (empty($build_info['args'])) {
      $args = func_get_args();
      // Remove $form and $form_state from the arguments.
      unset($args[0], $args[1]);
      $form_state->addBuildInfo('args', array_values($args));
    }

    // Populate FormState::$input with the submitted values before retrieving
    // the form, to be consistent with what self::buildForm() does for
    // non-programmatic submissions (form builder functions may expect it to be
    // there).
    $form_state->setUserInput($form_state->getValues());

    $form_state->setProgrammed();

    $form_id = $this->getFormId($form_arg, $form_state);
    $form = $this->retrieveForm($form_id, $form_state);
    // Programmed forms are always submitted.
    $form_state->setSubmitted();

    // Reset form validation.
    $form_state->setValidationEnforced();
    $form_state->clearErrors();

    $this->prepareForm($form_id, $form, $form_state);
    $this->processForm($form_id, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveForm($form_id, FormStateInterface &$form_state) {
    // Record the $form_id.
    $form_state->addBuildInfo('form_id', $form_id);

    // We save two copies of the incoming arguments: one for modules to use
    // when mapping form ids to constructor functions, and another to pass to
    // the constructor function itself.
    $build_info = $form_state->getBuildInfo();
    $args = $build_info['args'];

    $callback = [$form_state->getFormObject(), 'buildForm'];

    $form = array();
    // Assign a default CSS class name based on $form_id.
    // This happens here and not in self::prepareForm() in order to allow the
    // form constructor function to override or remove the default class.
    $form['#attributes']['class'][] = Html::getClass($form_id);
    // Same for the base form ID, if any.
    if (isset($build_info['base_form_id'])) {
      $form['#attributes']['class'][] = Html::getClass($build_info['base_form_id']);
    }

    // We need to pass $form_state by reference in order for forms to modify it,
    // since call_user_func_array() requires that referenced variables are
    // passed explicitly.
    $args = array_merge(array($form, &$form_state), $args);

    $form = call_user_func_array($callback, $args);
    // If the form returns a response, skip subsequent page construction by
    // throwing an exception.
    // @see Drupal\Core\EventSubscriber\EnforcedFormResponseSubscriber
    //
    // @todo Exceptions should not be used for code flow control. However, the
    //   Form API currently allows any form builder functions to return a
    //   response.
    //   @see https://www.drupal.org/node/2363189
    if ($form instanceof Response) {
      throw new EnforcedResponseException($form);
    }
    $form['#form_id'] = $form_id;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($form_id, &$form, FormStateInterface &$form_state) {
    $form_state->setValues([]);

    // With GET, these forms are always submitted if requested.
    if ($form_state->isMethodType('get') && $form_state->getAlwaysProcess()) {
      $input = $form_state->getUserInput();
      if (!isset($input['form_build_id'])) {
        $input['form_build_id'] = $form['#build_id'];
      }
      if (!isset($input['form_id'])) {
        $input['form_id'] = $form_id;
      }
      if (!isset($input['form_token']) && isset($form['#token'])) {
        $input['form_token'] = $this->csrfToken->get($form['#token']);
      }
      $form_state->setUserInput($input);
    }

    // self::doBuildForm() finishes building the form by calling element
    // #process functions and mapping user input, if any, to #value properties,
    // and also storing the values in $form_state->getValues(). We need to
    // retain the unprocessed $form in case it needs to be cached.
    $unprocessed_form = $form;
    $form = $this->doBuildForm($form_id, $form, $form_state);

    // Only process the input if we have a correct form submission.
    if ($form_state->isProcessingInput()) {
      // Form constructors may explicitly set #token to FALSE when cross site
      // request forgery is irrelevant to the form, such as search forms.
      if (isset($form['#token']) && $form['#token'] === FALSE) {
        unset($form['#token']);
      }
      // Form values for programmed form submissions typically do not include a
      // value for the submit button. But without a triggering element, a
      // potentially existing #limit_validation_errors property on the primary
      // submit button is not taken account. Therefore, check whether there is
      // exactly one submit button in the form, and if so, automatically use it
      // as triggering_element.
      $buttons = $form_state->getButtons();
      if ($form_state->isProgrammed() && !$form_state->getTriggeringElement() && count($buttons) == 1) {
        $form_state->setTriggeringElement(reset($buttons));
      }
      $this->formValidator->validateForm($form_id, $form, $form_state);

      // \Drupal\Component\Utility\Html::getUniqueId() maintains a cache of
      // element IDs it has seen, so it can prevent duplicates. We want to be
      // sure we reset that cache when a form is processed, so scenarios that
      // result in the form being built behind the scenes and again for the
      // browser don't increment all the element IDs needlessly.
      if (!FormState::hasAnyErrors()) {
        // In case of errors, do not break HTML IDs of other forms.
        Html::resetSeenIds();
      }

      // If there are no errors and the form is not rebuilding, submit the form.
      if (!$form_state->isRebuilding() && !FormState::hasAnyErrors()) {
        $submit_response = $this->formSubmitter->doSubmitForm($form, $form_state);
        // If this form was cached, delete it from the cache after submission.
        if ($form_state->isCached()) {
          $this->deleteCache($form['#build_id']);
        }
        // If the form submission directly returned a response, return it now.
        if ($submit_response) {
          return $submit_response;
        }
      }

      // Don't rebuild or cache form submissions invoked via self::submitForm().
      if ($form_state->isProgrammed()) {
        return;
      }

      // If $form_state->isRebuilding() has been set and input has been
      // processed without validation errors, we are in a multi-step workflow
      // that is not yet complete. A new $form needs to be constructed based on
      // the changes made to $form_state during this request. Normally, a submit
      // handler sets $form_state->isRebuilding() if a fully executed form
      // requires another step. However, for forms that have not been fully
      // executed (e.g., Ajax submissions triggered by non-buttons), there is no
      // submit handler to set $form_state->isRebuilding(). It would not make
      // sense to redisplay the identical form without an error for the user to
      // correct, so we also rebuild error-free non-executed forms, regardless
      // of $form_state->isRebuilding().
      // @todo Simplify this logic; considering Ajax and non-HTML front-ends,
      //   along with element-level #submit properties, it makes no sense to
      //   have divergent form execution based on whether the triggering element
      //   has #executes_submit_callback set to TRUE.
      if (($form_state->isRebuilding() || !$form_state->isExecuted()) && !FormState::hasAnyErrors()) {
        // Form building functions (e.g., self::handleInputElement()) may use
        // $form_state->isRebuilding() to determine if they are running in the
        // context of a rebuild, so ensure it is set.
        $form_state->setRebuild();
        $form = $this->rebuildForm($form_id, $form_state, $form);
      }
    }

    // After processing the form, the form builder or a #process callback may
    // have called $form_state->setCached() to indicate that the form and form
    // state shall be cached. But the form may only be cached if
    // $form_state->disableCache() is not called. Only cache $form as it was
    // prior to self::doBuildForm(), because self::doBuildForm() must run for
    // each request to accommodate new user input. Rebuilt forms are not cached
    // here, because self::rebuildForm() already takes care of that.
    if (!$form_state->isRebuilding() && $form_state->isCached()) {
      $this->setCache($form['#build_id'], $unprocessed_form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm($form_id, &$form, FormStateInterface &$form_state) {
    $user = $this->currentUser();

    $form['#type'] = 'form';

    // Only update the action if it is not already set.
    if (!isset($form['#action'])) {
      $form['#action'] = $this->buildFormAction();
    }

    // Fix the form method, if it is 'get' in $form_state, but not in $form.
    if ($form_state->isMethodType('get') && !isset($form['#method'])) {
      $form['#method'] = 'get';
    }

    // Generate a new #build_id for this form, if none has been set already.
    // The form_build_id is used as key to cache a particular build of the form.
    // For multi-step forms, this allows the user to go back to an earlier
    // build, make changes, and re-submit.
    // @see self::buildForm()
    // @see self::rebuildForm()
    if (!isset($form['#build_id'])) {
      $form['#build_id'] = 'form-' . Crypt::randomBytesBase64();
    }
    $form['form_build_id'] = array(
      '#type' => 'hidden',
      '#value' => $form['#build_id'],
      '#id' => $form['#build_id'],
      '#name' => 'form_build_id',
      // Form processing and validation requires this value, so ensure the
      // submitted form value appears literally, regardless of custom #tree
      // and #parents being set elsewhere.
      '#parents' => array('form_build_id'),
    );

    // Add a token, based on either #token or form_id, to any form displayed to
    // authenticated users. This ensures that any submitted form was actually
    // requested previously by the user and protects against cross site request
    // forgeries.
    // This does not apply to programmatically submitted forms. Furthermore,
    // since tokens are session-bound and forms displayed to anonymous users are
    // very likely cached, we cannot assign a token for them.
    // During installation, there is no $user yet.
    if ($user && $user->isAuthenticated() && !$form_state->isProgrammed()) {
      // Form constructors may explicitly set #token to FALSE when cross site
      // request forgery is irrelevant to the form, such as search forms.
      if (isset($form['#token']) && $form['#token'] === FALSE) {
        unset($form['#token']);
      }
      // Otherwise, generate a public token based on the form id.
      else {
        $form['#token'] = $form_id;
        $form['form_token'] = array(
          '#id' => Html::getUniqueId('edit-' . $form_id . '-form-token'),
          '#type' => 'token',
          '#default_value' => $this->csrfToken->get($form['#token']),
          // Form processing and validation requires this value, so ensure the
          // submitted form value appears literally, regardless of custom #tree
          // and #parents being set elsewhere.
          '#parents' => array('form_token'),
        );
      }
    }

    if (isset($form_id)) {
      $form['form_id'] = array(
        '#type' => 'hidden',
        '#value' => $form_id,
        '#id' => Html::getUniqueId("edit-$form_id"),
        // Form processing and validation requires this value, so ensure the
        // submitted form value appears literally, regardless of custom #tree
        // and #parents being set elsewhere.
        '#parents' => array('form_id'),
      );
    }
    if (!isset($form['#id'])) {
      $form['#id'] = Html::getUniqueId($form_id);
      // Provide a selector usable by JavaScript. As the ID is unique, its not
      // possible to rely on it in JavaScript.
      $form['#attributes']['data-drupal-selector'] = Html::getId($form_id);
    }

    $form += $this->elementInfo->getInfo('form');
    $form += array('#tree' => FALSE, '#parents' => array());
    $form['#validate'][] = '::validateForm';
    $form['#submit'][] = '::submitForm';

    $build_info = $form_state->getBuildInfo();
    // If no #theme has been set, automatically apply theme suggestions.
    // The form theme hook itself, which is rendered by form.html.twig,
    // is in #theme_wrappers. Therefore, the #theme function only has to care
    // for rendering the inner form elements, not the form itself.
    if (!isset($form['#theme'])) {
      $form['#theme'] = array($form_id);
      if (isset($build_info['base_form_id'])) {
        $form['#theme'][] = $build_info['base_form_id'];
      }
    }

    // Invoke hook_form_alter(), hook_form_BASE_FORM_ID_alter(), and
    // hook_form_FORM_ID_alter() implementations.
    $hooks = array('form');
    if (isset($build_info['base_form_id'])) {
      $hooks[] = 'form_' . $build_info['base_form_id'];
    }
    $hooks[] = 'form_' . $form_id;
    $this->moduleHandler->alter($hooks, $form, $form_state, $form_id);
    $this->themeManager->alter($hooks, $form, $form_state, $form_id);
  }

  /**
   * Builds the $form['#action'].
   *
   * @return string
   *   The URL to be used as the $form['#action'].
   */
  protected function buildFormAction() {
    // @todo Use <current> instead of the master request in
    //   https://www.drupal.org/node/2505339.
    $request = $this->requestStack->getMasterRequest();
    $request_uri = $request->getRequestUri();

    // Prevent cross site requests via the Form API by using an absolute URL
    // when the request uri starts with multiple slashes..
    if (strpos($request_uri, '//') === 0) {
      $request_uri = $request->getUri();
    }

    // @todo Remove this parsing once these are removed from the request in
    //   https://www.drupal.org/node/2504709.
    $parsed = UrlHelper::parse($request_uri);
    unset($parsed['query'][static::AJAX_FORM_REQUEST], $parsed['query'][MainContentViewSubscriber::WRAPPER_FORMAT]);
    return $parsed['path'] . ($parsed['query'] ? ('?' . UrlHelper::buildQuery($parsed['query'])) : '');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm($form_id, &$form, FormStateInterface &$form_state) {
    $this->formValidator->validateForm($form_id, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function redirectForm(FormStateInterface $form_state) {
    return $this->formSubmitter->redirectForm($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function executeValidateHandlers(&$form, FormStateInterface &$form_state) {
    $this->formValidator->executeValidateHandlers($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function executeSubmitHandlers(&$form, FormStateInterface &$form_state) {
    $this->formSubmitter->executeSubmitHandlers($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function doSubmitForm(&$form, FormStateInterface &$form_state) {
    throw new \LogicException('Use FormBuilderInterface::processForm() instead.');
  }

  /**
   * {@inheritdoc}
   */
  public function doBuildForm($form_id, &$element, FormStateInterface &$form_state) {
    // Initialize as unprocessed.
    $element['#processed'] = FALSE;

    // Use element defaults.
    if (isset($element['#type']) && empty($element['#defaults_loaded']) && ($info = $this->elementInfo->getInfo($element['#type']))) {
      // Overlay $info onto $element, retaining preexisting keys in $element.
      $element += $info;
      $element['#defaults_loaded'] = TRUE;
    }
    // Assign basic defaults common for all form elements.
    $element += array(
      '#required' => FALSE,
      '#attributes' => array(),
      '#title_display' => 'before',
      '#description_display' => 'after',
      '#errors' => NULL,
    );

    // Special handling if we're on the top level form element.
    if (isset($element['#type']) && $element['#type'] == 'form') {
      if (!empty($element['#https']) && !UrlHelper::isExternal($element['#action'])) {
        global $base_root;

        // Not an external URL so ensure that it is secure.
        $element['#action'] = str_replace('http://', 'https://', $base_root) . $element['#action'];
      }

      // Store a reference to the complete form in $form_state prior to building
      // the form. This allows advanced #process and #after_build callbacks to
      // perform changes elsewhere in the form.
      $form_state->setCompleteForm($element);

      // Set a flag if we have a correct form submission. This is always TRUE
      // for programmed forms coming from self::submitForm(), or if the form_id
      // coming from the POST data is set and matches the current form_id.
      $input = $form_state->getUserInput();
      if ($form_state->isProgrammed() || (!empty($input) && (isset($input['form_id']) && ($input['form_id'] == $form_id)))) {
        $form_state->setProcessInput();
      }
      else {
        $form_state->setProcessInput(FALSE);
      }

      // All form elements should have an #array_parents property.
      $element['#array_parents'] = array();
    }

    if (!isset($element['#id'])) {
      $unprocessed_id = 'edit-' . implode('-', $element['#parents']);
      $element['#id'] = Html::getUniqueId($unprocessed_id);
      // Provide a selector usable by JavaScript. As the ID is unique, its not
      // possible to rely on it in JavaScript.
      $element['#attributes']['data-drupal-selector'] = Html::getId($unprocessed_id);
    }
    else {
      // Provide a selector usable by JavaScript. As the ID is unique, its not
      // possible to rely on it in JavaScript.
      $element['#attributes']['data-drupal-selector'] = Html::getId($element['#id']);
    }

    // Add the aria-describedby attribute to associate the form control with its
    // description.
    if (!empty($element['#description'])) {
      $element['#attributes']['aria-describedby'] = $element['#id'] . '--description';
    }
    // Handle input elements.
    if (!empty($element['#input'])) {
      $this->handleInputElement($form_id, $element, $form_state);
    }
    // Allow for elements to expand to multiple elements, e.g., radios,
    // checkboxes and files.
    if (isset($element['#process']) && !$element['#processed']) {
      foreach ($element['#process'] as $callback) {
        $complete_form = &$form_state->getCompleteForm();
        $element = call_user_func_array($form_state->prepareCallback($callback), array(&$element, &$form_state, &$complete_form));
      }
      $element['#processed'] = TRUE;
    }

    // We start off assuming all form elements are in the correct order.
    $element['#sorted'] = TRUE;

    // Recurse through all child elements.
    $count = 0;
    if (isset($element['#access'])) {
      $access = $element['#access'];
      $inherited_access = NULL;
      if (($access instanceof AccessResultInterface && !$access->isAllowed()) || $access === FALSE) {
        $inherited_access = $access;
      }
    }
    foreach (Element::children($element) as $key) {
      // Prior to checking properties of child elements, their default
      // properties need to be loaded.
      if (isset($element[$key]['#type']) && empty($element[$key]['#defaults_loaded']) && ($info = $this->elementInfo->getInfo($element[$key]['#type']))) {
        $element[$key] += $info;
        $element[$key]['#defaults_loaded'] = TRUE;
      }

      // Don't squash an existing tree value.
      if (!isset($element[$key]['#tree'])) {
        $element[$key]['#tree'] = $element['#tree'];
      }

      // Children inherit #access from parent.
      if (isset($inherited_access)) {
        $element[$key]['#access'] = $inherited_access;
      }

      // Make child elements inherit their parent's #disabled and #allow_focus
      // values unless they specify their own.
      foreach (array('#disabled', '#allow_focus') as $property) {
        if (isset($element[$property]) && !isset($element[$key][$property])) {
          $element[$key][$property] = $element[$property];
        }
      }

      // Don't squash existing parents value.
      if (!isset($element[$key]['#parents'])) {
        // Check to see if a tree of child elements is present. If so,
        // continue down the tree if required.
        $element[$key]['#parents'] = $element[$key]['#tree'] && $element['#tree'] ? array_merge($element['#parents'], array($key)) : array($key);
      }
      // Ensure #array_parents follows the actual form structure.
      $array_parents = $element['#array_parents'];
      $array_parents[] = $key;
      $element[$key]['#array_parents'] = $array_parents;

      // Assign a decimal placeholder weight to preserve original array order.
      if (!isset($element[$key]['#weight'])) {
        $element[$key]['#weight'] = $count/1000;
      }
      else {
        // If one of the child elements has a weight then we will need to sort
        // later.
        unset($element['#sorted']);
      }
      $element[$key] = $this->doBuildForm($form_id, $element[$key], $form_state);
      $count++;
    }

    // The #after_build flag allows any piece of a form to be altered
    // after normal input parsing has been completed.
    if (isset($element['#after_build']) && !isset($element['#after_build_done'])) {
      foreach ($element['#after_build'] as $callback) {
        $element = call_user_func_array($form_state->prepareCallback($callback), array($element, &$form_state));
      }
      $element['#after_build_done'] = TRUE;
    }

    // If there is a file element, we need to flip a flag so later the
    // form encoding can be set.
    if (isset($element['#type']) && $element['#type'] == 'file') {
      $form_state->setHasFileElement();
    }

    // Final tasks for the form element after self::doBuildForm() has run for
    // all other elements.
    if (isset($element['#type']) && $element['#type'] == 'form') {
      // If there is a file element, we set the form encoding.
      if ($form_state->hasFileElement()) {
        $element['#attributes']['enctype'] = 'multipart/form-data';
      }

      // Allow Ajax submissions to the form action to bypass verification. This
      // is especially useful for multipart forms, which cannot be verified via
      // a response header.
      $element['#attached']['drupalSettings']['ajaxTrustedUrl'][$element['#action']] = TRUE;

      // If a form contains a single textfield, and the ENTER key is pressed
      // within it, Internet Explorer submits the form with no POST data
      // identifying any submit button. Other browsers submit POST data as
      // though the user clicked the first button. Therefore, to be as
      // consistent as we can be across browsers, if no 'triggering_element' has
      // been identified yet, default it to the first button.
      $buttons = $form_state->getButtons();
      if (!$form_state->isProgrammed() && !$form_state->getTriggeringElement() && !empty($buttons)) {
        $form_state->setTriggeringElement($buttons[0]);
      }

      $triggering_element = $form_state->getTriggeringElement();
      // If the triggering element specifies "button-level" validation and
      // submit handlers to run instead of the default form-level ones, then add
      // those to the form state.
      if (isset($triggering_element['#validate'])) {
        $form_state->setValidateHandlers($triggering_element['#validate']);
      }
      if (isset($triggering_element['#submit'])) {
        $form_state->setSubmitHandlers($triggering_element['#submit']);
      }

      // If the triggering element executes submit handlers, then set the form
      // state key that's needed for those handlers to run.
      if (!empty($triggering_element['#executes_submit_callback'])) {
        $form_state->setSubmitted();
      }

      // Special processing if the triggering element is a button.
      if (!empty($triggering_element['#is_button'])) {
        // Because there are several ways in which the triggering element could
        // have been determined (including from input variables set by
        // JavaScript or fallback behavior implemented for IE), and because
        // buttons often have their #name property not derived from their
        // #parents property, we can't assume that input processing that's
        // happened up until here has resulted in
        // $form_state->getValue(BUTTON_NAME) being set. But it's common for
        // forms to have several buttons named 'op' and switch on
        // $form_state->getValue('op') during submit handler execution.
        $form_state->setValue($triggering_element['#name'], $triggering_element['#value']);
      }
    }
    return $element;
  }

  /**
   * Adds the #name and #value properties of an input element before rendering.
   */
  protected function handleInputElement($form_id, &$element, FormStateInterface &$form_state) {
    if (!isset($element['#name'])) {
      $name = array_shift($element['#parents']);
      $element['#name'] = $name;
      if ($element['#type'] == 'file') {
        // To make it easier to handle files in file.inc, we place all
        // file fields in the 'files' array. Also, we do not support
        // nested file names.
        // @todo Remove this files prefix now?
        $element['#name'] = 'files[' . $element['#name'] . ']';
      }
      elseif (count($element['#parents'])) {
        $element['#name'] .= '[' . implode('][', $element['#parents']) . ']';
      }
      array_unshift($element['#parents'], $name);
    }

    // Setting #disabled to TRUE results in user input being ignored regardless
    // of how the element is themed or whether JavaScript is used to change the
    // control's attributes. However, it's good UI to let the user know that
    // input is not wanted for the control. HTML supports two attributes for:
    // this: http://www.w3.org/TR/html401/interact/forms.html#h-17.12. If a form
    // wants to start a control off with one of these attributes for UI
    // purposes, only, but still allow input to be processed if it's submitted,
    // it can set the desired attribute in #attributes directly rather than
    // using #disabled. However, developers should think carefully about the
    // accessibility implications of doing so: if the form expects input to be
    // enterable under some condition triggered by JavaScript, how would someone
    // who has JavaScript disabled trigger that condition? Instead, developers
    // should consider whether a multi-step form would be more appropriate
    // (#disabled can be changed from step to step). If one still decides to use
    // JavaScript to affect when a control is enabled, then it is best for
    // accessibility for the control to be enabled in the HTML, and disabled by
    // JavaScript on document ready.
    if (!empty($element['#disabled'])) {
      if (!empty($element['#allow_focus'])) {
        $element['#attributes']['readonly'] = 'readonly';
      }
      else {
        $element['#attributes']['disabled'] = 'disabled';
      }
    }

    // With JavaScript or other easy hacking, input can be submitted even for
    // elements with #access=FALSE or #disabled=TRUE. For security, these must
    // not be processed. Forms that set #disabled=TRUE on an element do not
    // expect input for the element, and even forms submitted with
    // self::submitForm() must not be able to get around this. Forms that set
    // #access=FALSE on an element usually allow access for some users, so forms
    // submitted with self::submitForm() may bypass access restriction and be
    // treated as high-privilege users instead.
    $process_input = empty($element['#disabled']) && (($form_state->isProgrammed() && $form_state->isBypassingProgrammedAccessChecks()) || ($form_state->isProcessingInput() && (!isset($element['#access']) || $element['#access'])));

    // Set the element's #value property.
    if (!isset($element['#value']) && !array_key_exists('#value', $element)) {
      // @todo Once all elements are converted to plugins in
      //   https://www.drupal.org/node/2311393, rely on
      //   $element['#value_callback'] directly.
      $value_callable = !empty($element['#value_callback']) ? $element['#value_callback'] : 'form_type_' . $element['#type'] . '_value';
      if (!is_callable($value_callable)) {
        $value_callable = '\Drupal\Core\Render\Element\FormElement::valueCallback';
      }

      if ($process_input) {
        // Get the input for the current element. NULL values in the input need
        // to be explicitly distinguished from missing input. (see below)
        $input_exists = NULL;
        $input = NestedArray::getValue($form_state->getUserInput(), $element['#parents'], $input_exists);
        // For browser-submitted forms, the submitted values do not contain
        // values for certain elements (empty multiple select, unchecked
        // checkbox). During initial form processing, we add explicit NULL
        // values for such elements in FormState::$input. When rebuilding the
        // form, we can distinguish elements having NULL input from elements
        // that were not part of the initially submitted form and can therefore
        // use default values for the latter, if required. Programmatically
        // submitted forms can submit explicit NULL values when calling
        // self::submitForm() so we do not modify FormState::$input for them.
        if (!$input_exists && !$form_state->isRebuilding() && !$form_state->isProgrammed()) {
          // Add the necessary parent keys to FormState::$input and sets the
          // element's input value to NULL.
          NestedArray::setValue($form_state->getUserInput(), $element['#parents'], NULL);
          $input_exists = TRUE;
        }
        // If we have input for the current element, assign it to the #value
        // property, optionally filtered through $value_callback.
        if ($input_exists) {
          $element['#value'] = call_user_func_array($value_callable, array(&$element, $input, &$form_state));

          if (!isset($element['#value']) && isset($input)) {
            $element['#value'] = $input;
          }
        }
        // Mark all posted values for validation.
        if (isset($element['#value']) || (!empty($element['#required']))) {
          $element['#needs_validation'] = TRUE;
        }
      }
      // Load defaults.
      if (!isset($element['#value'])) {
        // Call #type_value without a second argument to request default_value
        // handling.
        $element['#value'] = call_user_func_array($value_callable, array(&$element, FALSE, &$form_state));

        // Final catch. If we haven't set a value yet, use the explicit default
        // value. Avoid image buttons (which come with garbage value), so we
        // only get value for the button actually clicked.
        if (!isset($element['#value']) && empty($element['#has_garbage_value'])) {
          $element['#value'] = isset($element['#default_value']) ? $element['#default_value'] : '';
        }
      }
    }

    // Determine which element (if any) triggered the submission of the form and
    // keep track of all the clickable buttons in the form for
    // \Drupal\Core\Form\FormState::cleanValues(). Enforce the same input
    // processing restrictions as above.
    if ($process_input) {
      // Detect if the element triggered the submission via Ajax.
      if ($this->elementTriggeredScriptedSubmission($element, $form_state)) {
        $form_state->setTriggeringElement($element);
      }

      // If the form was submitted by the browser rather than via Ajax, then it
      // can only have been triggered by a button, and we need to determine
      // which button within the constraints of how browsers provide this
      // information.
      if (!empty($element['#is_button'])) {
        // All buttons in the form need to be tracked for
        // \Drupal\Core\Form\FormState::cleanValues() and for the
        // self::doBuildForm() code that handles a form submission containing no
        // button information in \Drupal::request()->request.
        $buttons = $form_state->getButtons();
        $buttons[] = $element;
        $form_state->setButtons($buttons);
        if ($this->buttonWasClicked($element, $form_state)) {
          $form_state->setTriggeringElement($element);
        }
      }
    }

    // Set the element's value in $form_state->getValues(), but only, if its key
    // does not exist yet (a #value_callback may have already populated it).
    if (!NestedArray::keyExists($form_state->getValues(), $element['#parents'])) {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

  /**
   * Detects if an element triggered the form submission via Ajax.
   *
   * This detects button or non-button controls that trigger a form submission
   * via Ajax or some other scriptable environment. These environments can set
   * the special input key '_triggering_element_name' to identify the triggering
   * element. If the name alone doesn't identify the element uniquely, the input
   * key '_triggering_element_value' may also be set to require a match on
   * element value. An example where this is needed is if there are several
   * // buttons all named 'op', and only differing in their value.
   */
  protected function elementTriggeredScriptedSubmission($element, FormStateInterface &$form_state) {
    $input = $form_state->getUserInput();
    if (!empty($input['_triggering_element_name']) && $element['#name'] == $input['_triggering_element_name']) {
      if (empty($input['_triggering_element_value']) || $input['_triggering_element_value'] == $element['#value']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines if a given button triggered the form submission.
   *
   * This detects button controls that trigger a form submission by being
   * clicked and having the click processed by the browser rather than being
   * captured by JavaScript. Essentially, it detects if the button's name and
   * value are part of the POST data, but with extra code to deal with the
   * convoluted way in which browsers submit data for image button clicks.
   *
   * This does not detect button clicks processed by Ajax (that is done in
   * self::elementTriggeredScriptedSubmission()) and it does not detect form
   * submissions from Internet Explorer in response to an ENTER key pressed in a
   * textfield (self::doBuildForm() has extra code for that).
   *
   * Because this function contains only part of the logic needed to determine
   * $form_state->getTriggeringElement(), it should not be called from anywhere
   * other than within the Form API. Form validation and submit handlers needing
   * to know which button was clicked should get that information from
   * $form_state->getTriggeringElement().
   */
  protected function buttonWasClicked($element, FormStateInterface &$form_state) {
    // First detect normal 'vanilla' button clicks. Traditionally, all standard
    // buttons on a form share the same name (usually 'op'), and the specific
    // return value is used to determine which was clicked. This ONLY works as
    // long as $form['#name'] puts the value at the top level of the tree of
    // \Drupal::request()->request data.
    $input = $form_state->getUserInput();
    if (isset($input[$element['#name']]) && $input[$element['#name']] == $element['#value']) {
      return TRUE;
    }
    // When image buttons are clicked, browsers do NOT pass the form element
    // value in \Drupal::request()->Request. Instead they pass an integer
    // representing the coordinates of the click on the button image. This means
    // that image buttons MUST have unique $form['#name'] values, but the
    // details of their \Drupal::request()->request data should be ignored.
    elseif (!empty($element['#has_garbage_value']) && isset($element['#value']) && $element['#value'] !== '') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Wraps file_upload_max_size().
   *
   * @return string
   *   A translated string representation of the size of the file size limit
   *   based on the PHP upload_max_filesize and post_max_size.
   */
  protected function getFileUploadMaxSize() {
    return file_upload_max_size();
  }

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  protected function currentUser() {
    if (!$this->currentUser && \Drupal::hasService('current_user')) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

}
