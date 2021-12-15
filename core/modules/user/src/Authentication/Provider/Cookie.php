<?php

namespace Drupal\user\Authentication\Provider;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Cookie based authentication provider.
 */
class Cookie implements AuthenticationProviderInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new cookie authentication provider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Messenger\MessengerInterface|null $messenger
   *   The messenger.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, Connection $connection, MessengerInterface $messenger = NULL) {
    $this->sessionConfiguration = $session_configuration;
    $this->connection = $connection;
    $this->messenger = $messenger;
    if ($this->messenger === NULL) {
      @trigger_error('The MessengerInterface must be passed to ' . __NAMESPACE__ . '\Cookie::__construct(). It was added in drupal:9.2.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $this->messenger = \Drupal::messenger();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $applies = $request->hasSession() && $this->sessionConfiguration->hasSession($request);
    if (!$applies && $request->query->has('check_logged_in')) {
      $domain = ltrim(ini_get('session.cookie_domain'), '.') ?: $request->getHttpHost();
      $this->messenger->addMessage($this->t('To log in to this site, your browser must accept cookies from the domain %domain.', ['%domain' => $domain]), 'error');
    }
    return $applies;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    return $this->getUserFromSession($request->getSession());
  }

  /**
   * Returns the UserSession object for the given session.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The UserSession object for the current user, or NULL if this is an
   *   anonymous session.
   */
  protected function getUserFromSession(SessionInterface $session) {
    if ($uid = $session->get('uid')) {
      // @todo Load the User entity in SessionHandler so we don't need queries.
      // @see https://www.drupal.org/node/2345611
      $values = $this->connection
        ->query('SELECT * FROM {users_field_data} [u] WHERE [u].[uid] = :uid AND [u].[default_langcode] = 1', [':uid' => $uid])
        ->fetchAssoc();

      // Check if the user data was found and the user is active.
      if (!empty($values) && $values['status'] == 1) {
        // Add the user's roles.
        $rids = $this->connection
          ->query('SELECT [roles_target_id] FROM {user__roles} WHERE [entity_id] = :uid', [':uid' => $values['uid']])
          ->fetchCol();
        $values['roles'] = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);

        return new UserSession($values);
      }
    }

    // This is an anonymous session.
    return NULL;
  }

  /**
   * Adds a query parameter to check successful log in redirect URL.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Event to process.
   */
  public function addCheckToUrl(ResponseEvent $event) {
    $response = $event->getResponse();
    if ($response instanceof RedirectResponse && $event->getRequest()->hasSession()) {
      if ($event->getRequest()->getSession()->has('check_logged_in')) {
        $event->getRequest()->getSession()->remove('check_logged_in');
        $url = $response->getTargetUrl();
        $options = UrlHelper::parse($url);
        $options['query']['check_logged_in'] = '1';
        $url = $options['path'] . '?' . UrlHelper::buildQuery($options['query']);
        if (!empty($options['#fragment'])) {
          $url .= '#' . $options['#fragment'];
        }
        // In the case of trusted redirect, we have to update the list of
        // trusted URLs because here we've just modified its target URL
        // which is in the list.
        if ($response instanceof TrustedRedirectResponse) {
          $response->setTrustedTargetUrl($url);
        }
        $response->setTargetUrl($url);
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['addCheckToUrl', -1000];
    return $events;
  }

}
