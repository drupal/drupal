<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlPageController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for most HTML pages.
 */
class HtmlPageController extends HtmlControllerBase {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * Constructs a new HtmlPageController.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $translation_manager, TitleResolverInterface $title_resolver) {
    parent::__construct($translation_manager, $title_resolver);

    $this->controllerResolver = $controller_resolver;
  }

  /**
   * Controller method for generic HTML pages.
   *
   * @param Request $request
   *   The request object.
   * @param callable $_content
   *   The body content callable that contains the body region of this page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function content(Request $request, $_content) {
    $page_content = $this->getContentResult($request, $_content);
    return $this->createHtmlFragment($page_content, $request);
  }

  /**
   * Returns the result of invoking the sub-controller.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param mixed $controller_definition
   *   A controller definition string, or a callable object/closure.
   *
   * @return array
   *   The render array that results from invoking the controller.
   */
  public function getContentResult(Request $request, $controller_definition) {
    if ($controller_definition instanceof \Closure) {
      $callable = $controller_definition;
    }
    else {
      $callable = $this->controllerResolver->getControllerFromDefinition($controller_definition);
    }
    $arguments = $this->controllerResolver->getArguments($request, $callable);
    $page_content = call_user_func_array($callable, $arguments);

    return $page_content;
  }

}
