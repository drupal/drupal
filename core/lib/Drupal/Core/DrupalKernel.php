<?php

/**
 * @file
 *
 * Definition of Drupal\Core\DrupalKernel.
 */

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
//use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Drupal\Core\EventSubscriber\ViewSubscriber;
use Drupal\Core\EventSubscriber\AccessSubscriber;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\EventSubscriber\PathSubscriber;
use Drupal\Core\EventSubscriber\LegacyRequestSubscriber;
use Drupal\Core\EventSubscriber\LegacyControllerSubscriber;
use Drupal\Core\EventSubscriber\MaintenanceModeSubscriber;
use Drupal\Core\EventSubscriber\RequestCloseSubscriber;
use Drupal\Core\EventSubscriber\RouterListener;

use Exception;

/**
 * The DrupalKernel class is the core of Drupal itself.
 */
class DrupalKernel extends HttpKernel {

    /**
     * The event dispatcher used by this kernel.
     *
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * The controller resolver that will extract the controller from a Request.
     *
     * @var ControllerResolverInterface
     */
    protected $resolver;


    /**
     * Constructor
     *
     * @param EventDispatcherInterface $dispatcher
     *   An EventDispatcherInterface instance
     * @param ControllerResolverInterface $resolver
     *   A ControllerResolverInterface instance
     */
   public function __construct(EventDispatcherInterface $dispatcher, ControllerResolverInterface $resolver) {
      parent::__construct($dispatcher, $resolver);
      $this->dispatcher = $dispatcher;
      $this->resolver = $resolver;

      $this->matcher = new UrlMatcher();
      $this->dispatcher->addSubscriber(new RouterListener($this->matcher));

      $negotiation = new ContentNegotiation();

      // @todo Make this extensible rather than just hard coding some.
      // @todo Add a subscriber to handle other things, too, like our Ajax
      // replacement system.
      $this->dispatcher->addSubscriber(new ViewSubscriber($negotiation));
      $this->dispatcher->addSubscriber(new AccessSubscriber());
      $this->dispatcher->addSubscriber(new MaintenanceModeSubscriber());
      $this->dispatcher->addSubscriber(new PathSubscriber());
      $this->dispatcher->addSubscriber(new LegacyRequestSubscriber());
      $this->dispatcher->addSubscriber(new LegacyControllerSubscriber());
      $this->dispatcher->addSubscriber(new FinishResponseSubscriber());
      $this->dispatcher->addSubscriber(new RequestCloseSubscriber());

      // Some other form of error occured that wasn't handled by another kernel
      // listener.  That could mean that it's a method/mime-type/error
      // combination that is not accounted for, or some other type of error.
      // Either way, treat it as a server-level error and return an HTTP 500.
      // By default, this will be an HTML-type response because that's a decent
      // best guess if we don't know otherwise.
      $this->dispatcher->addSubscriber(new ExceptionListener(array(new ExceptionController($this, $negotiation), 'execute')));
    }
}
