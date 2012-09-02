<?php

namespace Drupal\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HtmlPageController implements ContainerAwareInterface {

  /**
   * The injection container for this object.
   *
   * @var ContainerInterface
   */
  protected $container;

  public function setContainer(ContainerInterface $container = NULL) {
    $this->container = $container;
  }

  public function content(Request $request, $_content) {

    // @todo When we have a Generator, we can replace the forward() call with
    // a render() call, which would handle ESI and hInclude as well.  That will
    // require an _internal route.  For examples, see:
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing/internal.xml
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/InternalController.php
    $attributes = $request->attributes;
    $controller = $attributes->get('_content');
    $attributes->remove('system_path');
    $attributes->remove('_content');
    $response = $this->container->get('http_kernel')->forward($controller, $attributes->all(), $request->query->all());

    $page_content = $response->getContent();

    return new Response(drupal_render_page($page_content));
  }

  protected function getContentController($controller) {
    if (is_array($controller) || (is_object($controller) && method_exists($controller, '__invoke'))) {
      return $controller;
    }

    if (FALSE === strpos($controller, ':')) {
      if (method_exists($controller, '__invoke')) {
        return new $controller;
      } elseif (function_exists($controller)) {
        return $controller;
      }
    }

    list($controller, $method) = $this->createController($controller);

    if (!method_exists($controller, $method)) {
      throw new \InvalidArgumentException(sprintf('Method "%s::%s" does not exist.', get_class($controller), $method));
    }

    return array($controller, $method);
  }

  protected function createController($controller) {
    if (false === strpos($controller, '::')) {
     throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
    }

    list($class, $method) = explode('::', $controller, 2);

    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
    }

    return array(new $class(), $method);
  }


}
