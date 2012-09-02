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

    $content_controller = $this->getContentController($_content);

    $page_callback_result = call_user_func_array($content_controller, array());

    return new Response(drupal_render_page($page_callback_result));
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
