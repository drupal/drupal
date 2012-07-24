<?php

/**
 * @file
 * Definition of Drupal\Core\HttpKernel.
 */

namespace Drupal\Core;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernel as BaseHttpKernel;


/**
 * This HttpKernel is used to manage scope changes of the DI container.
 */
class HttpKernel extends BaseHttpKernel
{
    protected $container;

    public function __construct(ContainerAwareEventDispatcher $dispatcher, ContainerInterface $container, ControllerResolverInterface $controllerResolver)
    {
        parent::__construct($dispatcher, $controllerResolver);

        $this->container = $container;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $request->headers->set('X-Php-Ob-Level', ob_get_level());

        $this->container->enterScope('request');
        $this->container->set('request', $request, 'request');

        try {
            $response = parent::handle($request, $type, $catch);
        } catch (\Exception $e) {
            $this->container->leaveScope('request');

            throw $e;
        }

        $this->container->leaveScope('request');

        return $response;
    }
}
