<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormCache.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Encapsulates the caching of a form and its form state.
 *
 * @ingroup form_api
 */
class FormCache implements FormCacheInterface {

  /**
   * The factory for expirable key value stores used by form cache.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirableFactory;

  /**
   * The CSRF token generator to validate the form token.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new FormCache.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable_factory
   *   The key value expirable factory, used to create key value expirable
   *   stores for the form cache and form state cache.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expirable_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user, CsrfTokenGenerator $csrf_token = NULL) {
    $this->keyValueExpirableFactory = $key_value_expirable_factory;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function getCache($form_build_id, FormStateInterface $form_state) {
    if ($form = $this->keyValueExpirableFactory->get('form')->get($form_build_id)) {
      if ((isset($form['#cache_token']) && $this->csrfToken->validate($form['#cache_token'])) || (!isset($form['#cache_token']) && $this->currentUser->isAnonymous())) {
        $this->loadCachedFormState($form_build_id, $form_state);
        return $form;
      }
    }
  }

  /**
   * Loads the cached form state.
   *
   * @param string $form_build_id
   *   The unique form build ID.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function loadCachedFormState($form_build_id, FormStateInterface $form_state) {
    if ($stored_form_state = $this->keyValueExpirableFactory->get('form_state')->get($form_build_id)) {
      // Re-populate $form_state for subsequent rebuilds.
      $form_state->setFormState($stored_form_state);

      // If the original form is contained in include files, load the files.
      // @see \Drupal\Core\Form\FormStateInterface::loadInclude()
      $build_info = $form_state->getBuildInfo();
      $build_info += ['files' => []];
      foreach ($build_info['files'] as $file) {
        if (is_array($file)) {
          $file += array('type' => 'inc', 'name' => $file['module']);
          $this->moduleHandler->loadInclude($file['module'], $file['type'], $file['name']);
        }
        elseif (file_exists($file)) {
          require_once DRUPAL_ROOT . '/' . $file;
        }
      }
      // Retrieve the list of previously known safe strings and store it
      // for this request.
      // @todo Ensure we are not storing an excessively large string list
      //   in: https://www.drupal.org/node/2295823
      $build_info += ['safe_strings' => []];
      SafeMarkup::setMultiple($build_info['safe_strings']);
      unset($build_info['safe_strings']);
      $form_state->setBuildInfo($build_info);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCache($form_build_id, $form, FormStateInterface $form_state) {
    // 6 hours cache life time for forms should be plenty.
    $expire = 21600;

    // Cache form structure.
    if (isset($form)) {
      if ($this->currentUser->isAuthenticated()) {
        $form['#cache_token'] = $this->csrfToken->get();
      }
      $this->keyValueExpirableFactory->get('form')->setWithExpire($form_build_id, $form, $expire);
    }

    // Cache form state.
    // Store the known list of safe strings for form re-use.
    // @todo Ensure we are not storing an excessively large string list in:
    //   https://www.drupal.org/node/2295823
    $form_state->addBuildInfo('safe_strings', SafeMarkup::getAll());

    if ($data = $form_state->getCacheableArray()) {
      $this->keyValueExpirableFactory->get('form_state')->setWithExpire($form_build_id, $data, $expire);
    }
  }

}
