<?php

namespace Drupal\Core\Database;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides replica server kill switch to ignore it.
 */
class ReplicaKillSwitch implements EventSubscriberInterface {

  /**
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a ReplicaKillSwitch object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings object.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(Settings $settings, TimeInterface $time, SessionInterface $session) {
    $this->settings = $settings;
    $this->time = $time;
    $this->session = $session;
  }

  /**
   * Denies access to replica database on the current request.
   *
   * @see https://www.drupal.org/node/2286193
   */
  public function trigger() {
    $connection_info = Database::getConnectionInfo();
    // Only set ignore_replica_server if there are replica servers being used,
    // which is assumed if there are more than one.
    if (count($connection_info) > 1) {
      // Five minutes is long enough to allow the replica to break and resume
      // interrupted replication without causing problems on the Drupal site
      // from the old data.
      $duration = $this->settings->get('maximum_replication_lag', 300);
      // Set session variable with amount of time to delay before using replica.
      $this->session->set('ignore_replica_server', $this->time->getRequestTime() + $duration);
    }
  }

  /**
   * Checks and disables the replica database server if appropriate.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function checkReplicaServer(GetResponseEvent $event) {
    // Ignore replica database servers for this request.
    //
    // In Drupal's distributed database structure, new data is written to the
    // master and then propagated to the replica servers.  This means there is a
    // lag between when data is written to the master and when it is available
    // on the replica. At these times, we will want to avoid using a replica
    // server temporarily. For example, if a user posts a new node then we want
    // to disable the replica server for that user temporarily to allow the
    // replica server to catch up.
    // That way, that user will see their changes immediately while for other
    // users we still get the benefits of having a replica server, just with
    // slightly stale data. Code that wants to disable the replica server should
    // use the 'database.replica_kill_switch' service's trigger() method to set
    // 'ignore_replica_server' session flag to the timestamp after which the
    // replica can be re-enabled.
    if ($this->session->has('ignore_replica_server')) {
      if ($this->session->get('ignore_replica_server') >= $this->time->getRequestTime()) {
        Database::ignoreTarget('default', 'replica');
      }
      else {
        $this->session->remove('ignore_replica_server');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkReplicaServer'];
    return $events;
  }

}
