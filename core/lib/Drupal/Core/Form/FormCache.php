<?php

namespace Drupal\Core\Form;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Encapsulates the caching of a form and its form state.
 *
 * @ingroup form_api
 */
class FormCache implements FormCacheInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FormCache.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirableFactory
   *   The key value expirable factory, used to create key value expirable
   *   stores for the form cache and form state cache.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $requestPolicy
   *   A policy rule determining the cacheability of a request.
   */
  public function __construct(
    protected $root,
    protected KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected AccountInterface $currentUser,
    protected CsrfTokenGenerator $csrfToken,
    protected LoggerInterface $logger,
    protected RequestStack $requestStack,
    protected RequestPolicyInterface $requestPolicy,
  ) {
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
          $file += ['type' => 'inc', 'name' => $file['module']];
          $this->moduleHandler->loadInclude($file['module'], $file['type'], $file['name']);
        }
        elseif (file_exists($file)) {
          require_once $this->root . '/' . $file;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCache($form_build_id, $form, FormStateInterface $form_state) {
    // Cache forms for 6 hours by default.
    $expire = Settings::get('form_cache_expiration', 21600);

    // Ensure that the form build_id embedded in the form structure is the same
    // as the one passed in as a parameter. This is an additional safety measure
    // to prevent legacy code operating directly with
    // \Drupal::formBuilder()->getCache() and \Drupal::formBuilder()->setCache()
    // from accidentally overwriting immutable form state.
    if (isset($form['#build_id']) && $form['#build_id'] != $form_build_id) {
      $this->logger->error('Form build-id mismatch detected while attempting to store a form in the cache.');
      return;
    }

    // Cache form structure.
    if (isset($form)) {
      if ($this->currentUser->isAuthenticated()) {
        $form['#cache_token'] = $this->csrfToken->get();
      }
      unset($form['#build_id_old']);
      $this->keyValueExpirableFactory->get('form')->setWithExpire($form_build_id, $form, $expire);
    }

    if ($data = $form_state->getCacheableArray()) {
      $this->keyValueExpirableFactory->get('form_state')->setWithExpire($form_build_id, $data, $expire);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCache($form_build_id) {
    $this->keyValueExpirableFactory->get('form')->delete($form_build_id);
    $this->keyValueExpirableFactory->get('form_state')->delete($form_build_id);
  }

}
