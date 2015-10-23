<?php

namespace Zumba\GastonJS\Browser;

use Zumba\GastonJS\Exception\BrowserError;
use Zumba\GastonJS\Exception\DeadClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

/**
 * Class BrowserBase
 * @package Zumba\GastonJS\Browser
 */
class BrowserBase {
  /** @var mixed */
  protected $logger;
  /** @var  bool */
  protected $debug;
  /** @var  string */
  protected $phantomJSHost;
  /** @var  Client */
  protected $apiClient;

  /**
   *  Creates an http client to consume the phantomjs API
   */
  protected function createApiClient() {
    // Provide a BC switch between guzzle 5 and guzzle 6.
    if (class_exists('GuzzleHttp\Psr7\Response')) {
      $this->apiClient = new Client(array("base_uri" => $this->getPhantomJSHost()));
    }
    else {
      $this->apiClient = new Client(array("base_url" => $this->getPhantomJSHost()));
    }
  }

  /**
   * TODO: not sure how to do the normalizeKeys stuff fix when needed
   * @param $keys
   * @return mixed
   */
  protected function normalizeKeys($keys) {
    return $keys;
  }

  /**
   * @return Client
   */
  public function getApiClient() {
    return $this->apiClient;
  }

  /**
   * @return string
   */
  public function getPhantomJSHost() {
    return $this->phantomJSHost;
  }

  /**
   * @return mixed
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Restarts the browser
   */
  public function restart() {
    //TODO: Do we really need to do this?, we are just a client
  }

  /**
   * Sends a command to the browser
   * @throws BrowserError
   * @throws \Exception
   * @return mixed
   */
  public function command() {
    try {
      $args = func_get_args();
      $commandName = $args[0];
      array_shift($args);
      $messageToSend = json_encode(array('name' => $commandName, 'args' => $args));
      /** @var $commandResponse \GuzzleHttp\Psr7\Response|\GuzzleHttp\Message\Response */
      $commandResponse = $this->getApiClient()->post("/api", array("body" => $messageToSend));
      $jsonResponse = json_decode($commandResponse->getBody(), TRUE);
    } catch (ServerException $e) {
      $jsonResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
    } catch (ConnectException $e) {
      throw new DeadClient($e->getMessage(), $e->getCode(), $e);
    } catch (\Exception $e) {
      throw $e;
    }

    if (isset($jsonResponse['error'])) {
      throw $this->getErrorClass($jsonResponse);
    }

    return $jsonResponse['response'];
  }

  /**
   * @param $error
   * @return BrowserError
   */
  protected function getErrorClass($error) {
    $errorClassMap = array(
      'Poltergeist.JavascriptError'   => "Zumba\\GastonJS\\Exception\\JavascriptError",
      'Poltergeist.FrameNotFound'     => "Zumba\\GastonJS\\Exception\\FrameNotFound",
      'Poltergeist.InvalidSelector'   => "Zumba\\GastonJS\\Exception\\InvalidSelector",
      'Poltergeist.StatusFailError'   => "Zumba\\GastonJS\\Exception\\StatusFailError",
      'Poltergeist.NoSuchWindowError' => "Zumba\\GastonJS\\Exception\\NoSuchWindowError",
      'Poltergeist.ObsoleteNode'      => "Zumba\\GastonJS\\Exception\\ObsoleteNode"
    );
    if (isset($error['error']['name']) && isset($errorClassMap[$error["error"]["name"]])) {
      return new $errorClassMap[$error["error"]["name"]]($error);
    }

    return new BrowserError($error);
  }
}
