<?php

// @codingStandardsIgnoreFile
// cspell:disable

namespace Drupal\Core\Http;

use Drupal\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Symfony 6 bridge.
 *
 * This is a copy of Symfony\Component\HttpKernel\Event\KernelEvent
 * with two changes: adding an ::isMainRequest() method for forward
 * compatibility with Symfony 5.3+, and issuing a deprecation message from
 * ::isMasterRequest().
 */
class KernelEvent extends Event
{
    private $kernel;
    private $request;
    private $requestType;

    /**
     * @param int $requestType The request type the kernel is currently processing; one of
     *                         HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST
     */
    public function __construct(HttpKernelInterface $kernel, Request $request, ?int $requestType)
    {
        $this->kernel = $kernel;
        $this->request = $request;
        $this->requestType = $requestType;
    }

    /**
     * Returns the kernel in which this event was thrown.
     *
     * @return HttpKernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Returns the request the kernel is currently processing.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the request type the kernel is currently processing.
     *
     * @return int One of HttpKernelInterface::MASTER_REQUEST and
     *             HttpKernelInterface::SUB_REQUEST
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Checks if this is a master request.
     *
     * @return bool True if the request is a master request
     */
    public function isMasterRequest()
    {
        @trigger_error('Symfony\Component\HttpKernel\Event\KernelEvent::isMasterRequest() is deprecated, use isMainRequest()', E_USER_DEPRECATED);
        return HttpKernelInterface::MASTER_REQUEST === $this->requestType;
    }

    /**
     * Checks if this is a main request.
     *
     * @return bool True if the request is a main request
     */
    public function isMainRequest()
    {
        return HttpKernelInterface::MASTER_REQUEST === $this->requestType;
    }
}
