<?php

namespace Drupal\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Proxy\AbstractProxy;

/**
 * Default session handler.
 */
class SessionHandler extends AbstractProxy implements \SessionHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new SessionHandler instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(RequestStack $request_stack, Connection $connection) {
    $this->requestStack = $request_stack;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function open(string $save_path, string $name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function read(#[\SensitiveParameter] string $sid) {
    $data = '';
    if (!empty($sid)) {
      // Read the session data from the database.
      $query = $this->connection
        ->queryRange('SELECT [session] FROM {sessions} WHERE [sid] = :sid', 0, 1, [':sid' => Crypt::hashBase64($sid)]);
      $data = (string) $query->fetchField();
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function write(#[\SensitiveParameter] string $sid, $value) {
    $request = $this->requestStack->getCurrentRequest();
    $fields = [
      'uid' => $request->getSession()->get('uid', 0),
      'hostname' => $request->getClientIP(),
      'session' => $value,
      'timestamp' => REQUEST_TIME,
    ];
    $this->connection->merge('sessions')
      ->keys(['sid' => Crypt::hashBase64($sid)])
      ->fields($fields)
      ->execute();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function close() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function destroy(#[\SensitiveParameter] string $sid) {
    // Delete session data.
    $this->connection->delete('sessions')
      ->condition('sid', Crypt::hashBase64($sid))
      ->execute();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function gc(int $lifetime) {
    // Be sure to adjust 'php_value session.gc_maxlifetime' to a large enough
    // value. For example, if you want user sessions to stay in your database
    // for three weeks before deleting them, you need to set gc_maxlifetime
    // to '1814400'. At that value, only after a user doesn't log in after
    // three weeks (1814400 seconds) will their session be removed.
    return $this->connection->delete('sessions')
      ->condition('timestamp', REQUEST_TIME - $lifetime, '<')
      ->execute();
  }

}
