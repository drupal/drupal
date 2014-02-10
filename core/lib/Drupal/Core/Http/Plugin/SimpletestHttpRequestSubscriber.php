<?php
/**
 * @file
 * Contains Drupal\Core\Http\Plugin\SimpletestHttpRequestSubscriber
 */

namespace Drupal\Core\Http\Plugin;

use Guzzle\Common\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to HTTP requests and override the User-Agent header if the request
 * is being dispatched from inside a simpletest.
 */
class SimpletestHttpRequestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    return array('request.before_send' => 'onBeforeSendRequest');
  }


  /**
   * Event callback for request.before_send
   */
  public function onBeforeSendRequest(Event $event) {
    // If the database prefix is being used by SimpleTest to run the tests in a copied
    // database then set the user-agent header to the database prefix so that any
    // calls to other Drupal pages will run the SimpleTest prefixed database. The
    // user-agent is used to ensure that multiple testing sessions running at the
    // same time won't interfere with each other as they would if the database
    // prefix were stored statically in a file or database variable.
    if ($test_prefix = drupal_valid_test_ua()) {
      $event['request']->setHeader('User-Agent', drupal_generate_test_ua($test_prefix));
    }
  }
}
