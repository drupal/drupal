<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\HtmlPageController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Default controller for most HTML pages.
 */
class HtmlPageController {

  /**
   * The HttpKernel object to use for subrequests.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolver
   */
  protected $titleResolver;

  /**
   * Constructs a new HtmlPageController.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   * @param \Drupal\Core\Controller\TitleResolver $title_resolver
   *   The title resolver.
   */
  public function __construct(HttpKernelInterface $kernel, ControllerResolverInterface $controller_resolver, TranslationInterface $translation_manager, TitleResolver $title_resolver) {
    $this->httpKernel = $kernel;
    $this->controllerResolver = $controller_resolver;
    $this->translationManager = $translation_manager;
    $this->titleResolver = $title_resolver;
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
    $callable = $this->controllerResolver->getControllerFromDefinition($_content);
    $arguments = $this->controllerResolver->getArguments($request, $callable);
    $page_content = call_user_func_array($callable, $arguments);
    if ($page_content instanceof Response) {
      return $page_content;
    }
    if (!is_array($page_content)) {
      $page_content = array(
        '#markup' => $page_content,
      );
    }
    if (!isset($page_content['#title'])) {
      $title = $this->titleResolver->getTitle($request, $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT));
      // Ensure that #title will not be set if no title was returned.
      if (isset($title)) {
        $page_content['#title'] = $title;
      }
    }

    $response = new Response(drupal_render_page($page_content));
    return $response;
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
