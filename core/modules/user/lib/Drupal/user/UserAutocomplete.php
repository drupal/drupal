<?php

/**
 * @file
 * Contains \Drupal\user\UserAutocomplete.
 */

namespace Drupal\user;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;

/**
 * Defines a helper class to get user autocompletion results.
 */
class UserAutocomplete {

  /**
   * The database connection to query for the user names.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory to get the anonymous user name.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a UserAutocomplete object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to query for the user names.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Connection $connection, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * Get matches for the autocompletion of user names.
   *
   * @param string $string
   *   The string to match for usernames.
   *
   * @param bool $include_anonymous
   *   (optional) TRUE if the the name used to indicate anonymous users (e.g.
   *   "Anonymous") should be autocompleted. Defaults to FALSE.
   *
   * @return array
   *   An array containing the matching usernames.
   */
  public function getMatches($string, $include_anonymous = FALSE) {
    $matches = array();
    if ($string) {
      if ($include_anonymous) {
        $anonymous_name = $this->configFactory->get('user.settings')->get('anonymous');
        // Allow autocompletion for the anonymous user.
        if (stripos($anonymous_name, $string) !== FALSE) {
          $matches[$anonymous_name] = check_plain($anonymous_name);
        }
      }
      $result = $this->connection->select('users')->fields('users', array('name'))->condition('name', db_like($string) . '%', 'LIKE')->range(0, 10)->execute();
      foreach ($result as $account) {
        $matches[$account->name] = check_plain($account->name);
      }
    }

    return $matches;
  }

}
