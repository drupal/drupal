<?php

namespace Drupal\user\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Cookie based authentication provider.
 */
class Cookie implements AuthenticationProviderInterface {

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
   * Constructs a new cookie authentication provider.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, Connection $connection) {
    $this->sessionConfiguration = $session_configuration;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $request->hasSession() && $this->sessionConfiguration->hasSession($request);
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
   * @return \Drupal\Core\Session\AccountInterface|NULL
   *   The UserSession object for the current user, or NULL if this is an
   *   anonymous session.
   */
  protected function getUserFromSession(SessionInterface $session) {
    if ($uid = $session->get('uid')) {
      // @todo Load the User entity in SessionHandler so we don't need queries.
      // @see https://www.drupal.org/node/2345611
      $values = $this->connection
        ->query('SELECT * FROM {users_field_data} u WHERE u.uid = :uid AND u.default_langcode = 1', [':uid' => $uid])
        ->fetchAssoc();

      // Check if the user data was found and the user is active.
      if (!empty($values) && $values['status'] == 1) {
        // Add the user's roles.
        $rids = $this->connection
          ->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = :uid', [':uid' => $values['uid']])
          ->fetchCol();
        $values['roles'] = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);

        return new UserSession($values);
      }
    }

    // This is an anonymous session.
    return NULL;
  }

}
