<?php

namespace Zumba\GastonJS\Browser;

use Zumba\GastonJS\NetworkTraffic\Request;

/**
 * Trait BrowserNetworkTrait
 * @package Zumba\GastonJS\Browser
 */
trait BrowserNetworkTrait {
  /**
   * Get all the network traffic that the page have created
   * @return array
   */
  public function networkTraffic() {
    $networkTraffic = $this->command('network_traffic');
    $requestTraffic = array();

    if (count($networkTraffic) === 0) {
      return null;
    }

    foreach ($networkTraffic as $traffic) {
      $requestTraffic[] = new Request($traffic["request"], $traffic["responseParts"]);
    }

    return $requestTraffic;
  }

  /**
   * Clear the network traffic data stored on the phantomjs code
   * @return mixed
   */
  public function clearNetworkTraffic() {
    return $this->command('clear_network_traffic');
  }

}
