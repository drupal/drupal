<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\SlaveDatabaseIgnoreSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System subscriber for controller requests.
 */
class SlaveDatabaseIgnoreSubscriber implements EventSubscriberInterface {

  /**
   * Checks and disables the slave database server if appropriate.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function checkSlaveServer(GetResponseEvent $event) {
    // Ignore slave database servers for this request.
    //
    // In Drupal's distributed database structure, new data is written to the
    // master and then propagated to the slave servers.  This means there is a
    // lag between when data is written to the master and when it is available
    // on the slave. At these times, we will want to avoid using a slave server
    // temporarily. For example, if a user posts a new node then we want to
    // disable the slave server for that user temporarily to allow the slave
    // server to catch up.
    // That way, that user will see their changes immediately while for other
    // users we still get the benefits of having a slave server, just with
    // slightly stale data.  Code that wants to disable the slave server should
    // use the db_set_ignore_slave() function to set
    // $_SESSION['ignore_slave_server'] to the timestamp after which the slave
    // can be re-enabled.
    if (isset($_SESSION['ignore_slave_server'])) {
      if ($_SESSION['ignore_slave_server'] >= REQUEST_TIME) {
        Database::ignoreTarget('default', 'slave');
      }
      else {
        unset($_SESSION['ignore_slave_server']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkSlaveServer');
    return $events;
  }

}
