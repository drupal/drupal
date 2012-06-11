<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\UrlMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * UrlMatcher matches URL based on a set of routes.
 */
class UrlMatcher implements UrlMatcherInterface {

  /**
   * The request context for this matcher.
   *
   * @var Symfony\Component\Routing\RequestContext
   */
  protected $context;

  /**
   * The request object for this matcher.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructor.
   */
  public function __construct() {
    // We will not actually use this object, but it's needed to conform to
    // the interface.
    $this->context = new RequestContext();
  }

  /**
   * Sets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @param Symfony\Component\Routing\RequestContext $context
   *   The context.
   *
   * @api
   */
  public function setContext(RequestContext $context) {
    $this->context = $context;
  }

  /**
   * Gets the request context.
   *
   * This method is just to satisfy the interface, and is largely vestigial.
   * The request context object does not contain the information we need, so
   * we will use the original request object.
   *
   * @return Symfony\Component\Routing\RequestContext
   *   The context.
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Sets the request object to use.
   *
   * This is used by the RouterListener to make additional request attributes
   * available.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Gets the request object.
   *
   * @return Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function getRequest() {
    return $this->request;
  }

  public function match($pathinfo) {

  }

}