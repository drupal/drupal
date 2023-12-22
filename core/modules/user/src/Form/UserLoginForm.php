<?php

namespace Drupal\user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Url;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drupal\user\UserFloodControlInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user login form.
 *
 * @internal
 */
class UserLoginForm extends FormBase {

  /**
   * The user flood control service.
   *
   * @var \Drupal\user\UserFloodControl
   */
  protected $userFloodControl;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The bare HTML renderer.
   *
   * @var \Drupal\Core\Render\BareHtmlPageRendererInterface
   */
  protected $bareHtmlPageRenderer;

  /**
   * Constructs a new UserLoginForm.
   *
   * @param \Drupal\user\UserFloodControlInterface $user_flood_control
   *   The user flood control service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_renderer
   *   The renderer.
   */
  public function __construct(UserFloodControlInterface $user_flood_control, UserStorageInterface $user_storage, UserAuthInterface $user_auth, RendererInterface $renderer, BareHtmlPageRendererInterface $bare_html_renderer) {
    $this->userFloodControl = $user_flood_control;
    $this->userStorage = $user_storage;
    $this->userAuth = $user_auth;
    $this->renderer = $renderer;
    $this->bareHtmlPageRenderer = $bare_html_renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.flood_control'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('renderer'),
      $container->get('bare_html_page_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system.site');

    // Display login form:
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 60,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#required' => TRUE,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
        'autocomplete' => 'username',
      ],
    ];

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
      '#required' => TRUE,
      '#attributes' => [
        'autocomplete' => 'current-password',
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Log in')];

    $form['#validate'][] = '::validateName';
    $form['#validate'][] = '::validateAuthentication';
    $form['#validate'][] = '::validateFinal';

    $this->renderer->addCacheableDependency($form, $config);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (empty($uid = $form_state->get('uid'))) {
      return;
    }
    $account = $this->userStorage->load($uid);

    // A destination was set, probably on an exception controller.
    if (!$this->getRequest()->request->has('destination')) {
      $form_state->setRedirect(
        'entity.user.canonical',
        ['user' => $account->id()]
      );
    }
    else {
      $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
    }

    user_login_finalize($account);
  }

  /**
   * Sets an error if supplied username has been blocked.
   */
  public function validateName(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('name') && user_is_blocked($form_state->getValue('name'))) {
      // Blocked in user administration.
      $form_state->setErrorByName('name', $this->t('The username %name has not been activated or is blocked.', ['%name' => $form_state->getValue('name')]));
    }
  }

  /**
   * Checks supplied username/password against local users table.
   *
   * If successful, $form_state->get('uid') is set to the matching user ID.
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->isValueEmpty('name') && strlen($password) > 0) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP for all users at an institution.
      if (!$this->userFloodControl->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        return;
      }
      $accounts = $this->userStorage->loadByProperties(['name' => $form_state->getValue('name'), 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // If there are zero flood records for this user, then we don't need to
        // clear any failed login attempts after a successful login, so check
        // for this case first before checking the actual flood limit and store
        // the result in form state.
        if (!$this->userFloodControl->isAllowed('user.failed_login_user', 1, $flood_config->get('user_window'), $identifier)) {
          // Now check the actual limit for the user. Default is to allow 5
          // failed attempts every 6 hours. This means we check the flood table
          // twice if flood control has already been triggered by a previous
          // login attempt, bu this should be the less common case.
          if (!$this->userFloodControl->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
            $form_state->set('flood_control_triggered', 'user');
            return;
          }
        }
        else {
          $form_state->set('flood_control_skip_clear', 'user');
        }
      }
      // We are not limited by flood control, so try to authenticate.
      // Store $uid in form state as a flag for self::validateFinal().
      $uid = $this->userAuth->authenticate($form_state->getValue('name'), $password);
      $form_state->set('uid', $uid);
    }
  }

  /**
   * Checks if user was not authenticated, or if too many logins were attempted.
   *
   * This validation function should always be the last one.
   */
  public function validateFinal(array &$form, FormStateInterface $form_state) {
    $flood_config = $this->config('user.flood');
    if (!$form_state->get('uid')) {
      // Always register an IP-based failed login event.
      $this->userFloodControl->register('user.failed_login_ip', $flood_config->get('ip_window'));
      // Register a per-user failed login event.
      if ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
        $this->userFloodControl->register('user.failed_login_user', $flood_config->get('user_window'), $flood_control_user_identifier);
      }

      if ($flood_control_triggered = $form_state->get('flood_control_triggered')) {
        if ($flood_control_triggered == 'user') {
          $message = $this->formatPlural($flood_config->get('user_limit'), 'There has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', 'There have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')->toString()]);
        }
        else {
          // We did not find a uid, so the limit is IP-based.
          $message = $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')->toString()]);
        }
        $response = $this->bareHtmlPageRenderer->renderBarePage(['#markup' => $message], $this->t('Login failed'), 'maintenance_page');
        $response->setStatusCode(403);
        $form_state->setResponse($response);
      }
      else {
        $form_state->setErrorByName('name', $this->t('Unrecognized username or password. <a href=":password">Forgot your password?</a>', [':password' => Url::fromRoute('user.pass')->toString()]));
        $accounts = $this->userStorage->loadByProperties(['name' => $form_state->getValue('name')]);
        if (!empty($accounts)) {
          $this->logger('user')->notice('Login attempt failed for %user.', ['%user' => $form_state->getValue('name')]);
        }
        else {
          // If the username entered is not a valid user,
          // only store the IP address.
          $this->logger('user')->notice('Login attempt failed from %ip.', ['%ip' => $this->getRequest()->getClientIp()]);
        }
      }
    }
    elseif (!$form_state->get('flood_control_skip_clear') && $flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
      // Clear past failures for this user so as not to block a user who might
      // log in and out more than once in an hour.
      $this->userFloodControl->clear('user.failed_login_user', $flood_control_user_identifier);
    }
  }

}
