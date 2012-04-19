<?php

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpKernel\EventListener\RouterListener as SymfonyRouterListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Description of RouterListener
 *
 * @author crell
 */
class RouterListener extends SymfonyRouterListener {

  protected $urlMatcher;

  public function __construct(UrlMatcherInterface $urlMatcher, LoggerInterface $logger = null) {
    parent::__construct($urlMatcher, $logger);
    $this->urlMatcher = $urlMatcher;
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $this->urlMatcher->setRequest($event->getRequest());
    parent::onKernelRequest($event);
  }

}
