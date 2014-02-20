<?php

/**
 * @file
 * Definition of Drupal\ban\EventSubscriber\BanSubscriber.
 */

namespace Drupal\ban\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\ban\BanIpManager;
use Drupal\Component\Utility\String;

/**
 * Ban subscriber for controller requests.
 */
class BanSubscriber implements EventSubscriberInterface {

  /**
   * The manager used to check the IP against.
   *
   * @var \Drupal\ban\BanIpManager
   */
  protected $manager;

  /**
   * Construct the BanSubscriber.
   *
   * @param \Drupal\ban\BanIpManager $manager
   *   The manager used to check the IP against.
   */
  public function __construct(BanIpManager $manager) {
    $this->manager = $manager;
  }

  /**
   * Response with 403 if the visitor's IP address is banned.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestBannedIpCheck(GetResponseEvent $event) {
    $ip = $event->getRequest()->getClientIp();
    if ($this->manager->isDenied($ip)) {
      $response = new Response('Sorry, ' . String::checkPlain($ip) . ' has been banned.', 403);
      $event->setResponse($response);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestBannedIpCheck', 40);
    return $events;
  }

}
