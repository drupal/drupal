<?php

/**
 * @file
 * Contains \Drupal\Core\Http\Client.
 */

namespace Drupal\Core\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Drupal default HTTP client class.
 */
class Client extends GuzzleClient {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config = []) {
    $default_config = array(
      // Security consideration: we must not use the certificate authority
      // file shipped with Guzzle because it can easily get outdated if a
      // certificate authority is hacked. Instead, we rely on the certificate
      // authority file provided by the operating system which is more likely
      // going to be updated in a timely fashion. This overrides the default
      // path to the pem file bundled with Guzzle.
      'verify' => TRUE,
      'timeout' => 30,
      'headers' => array(
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+https://drupal.org/) ' . static::getDefaultUserAgent(),
      ),
    );

    // The entire config array is merged/configurable to allow Guzzle client
    // options outside of 'defaults' to be changed, such as 'adapter', or
    // 'message_factory'.
    $config = NestedArray::mergeDeep(array('defaults' => $default_config), $config, Settings::get('http_client_config', array()));

    parent::__construct($config);
  }

  /**
   * Attaches an event subscriber.
   *
   * @param \GuzzleHttp\Event\SubscriberInterface $subscriber
   *   The subscriber to attach.
   *
   * @see \GuzzleHttp\Event\Emitter::attach()
   */
  public function attach(SubscriberInterface $subscriber) {
    $this->getEmitter()->attach($subscriber);
  }

  /**
   * Detaches an event subscriber.
   *
   * @param \GuzzleHttp\Event\SubscriberInterface $subscriber
   *   The subscriber to detach.
   *
   * @see \GuzzleHttp\Event\Emitter::detach()
   */
  public function detach(SubscriberInterface $subscriber) {
    $this->getEmitter()->detach($subscriber);
  }

}
