<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System subscriber for controller requests.
 */
class ReplicaDatabaseIgnoreSubscriber implements EventSubscriberInterface {

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
    // on the replica. At these times, we will want to avoid using a replica server
    // temporarily. For example, if a user posts a new node then we want to
    // disable the replica server for that user temporarily to allow the replica
    // server to catch up.
    // That way, that user will see their changes immediately while for other
    // users we still get the benefits of having a replica server, just with
    // slightly stale data.  Code that wants to disable the replica server should
    // use the db_set_ignore_replica() function to set
    // $_SESSION['ignore_replica_server'] to the timestamp after which the replica
    // can be re-enabled.
    if (isset($_SESSION['ignore_replica_server'])) {
      if ($_SESSION['ignore_replica_server'] >= REQUEST_TIME) {
        Database::ignoreTarget('default', 'replica');
      }
      else {
        unset($_SESSION['ignore_replica_server']);
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
