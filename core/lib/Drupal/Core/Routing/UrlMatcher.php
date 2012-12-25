<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Cmf\Component\Routing\NestedMatcher\UrlMatcher as BaseUrlMatcher;

/**
 * Description of UrlMatcher
 *
 * @author crell
 */
class UrlMatcher extends BaseUrlMatcher {

  public function __construct() {}

  public function finalMatch(RouteCollection $collection, Request $request) {
    $this->routes = $collection;
    $context = new RequestContext();
    $context->fromRequest($request);
    $this->setContext($context);
    return $this->match('/' . $request->attributes->get('system_path'));
  }

}
