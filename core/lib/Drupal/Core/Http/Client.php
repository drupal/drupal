<?php

/**
 * @file
 * Contains \Drupal\Core\Http\Client.
 */

namespace Drupal\Core\Http;

use Drupal\Core\Http\Plugin\SimpletestHttpRequestSubscriber;
use Drupal\Component\Utility\NestedArray;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Drupal default HTTP client class.
 */
class Client extends GuzzleClient {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config = []) {
    $default_config = array(
      'defaults' => array(
        'config' => array(
          'curl' => array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_MAXREDIRS => 3,
          ),
        ),
        // Security consideration: we must not use the certificate authority
        // file shipped with Guzzle because it can easily get outdated if a
        // certificate authority is hacked. Instead, we rely on the certificate
        // authority file provided by the operating system which is more likely
        // going to be updated in a timely fashion. This overrides the default
        // path to the pem file bundled with Guzzle.
        'verify' => TRUE,
        'headers' => array(
          'User-Agent' => 'Drupal (+http://drupal.org/)',
        ),
      ),
    );
    $config = NestedArray::mergeDeep($default_config, $config);

    parent::__construct($config);

    $this->getEmitter()->attach(new SimpletestHttpRequestSubscriber());
  }


}
