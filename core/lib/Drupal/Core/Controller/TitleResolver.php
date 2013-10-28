<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\TitleResolver.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Provides the default implementation of the title resolver interface.
 */
class TitleResolver implements TitleResolverInterface {

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * Constructs a TitleResolver instance.
   *
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $translation_manager) {
    $this->controllerResolver = $controller_resolver;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) {
    $route_title = NULL;
    // A dynamic title takes priority. Route::getDefault() returns NULL if the
    // named default is not set.  By testing the value directly, we also avoid
    // trying to use empty values.
    if ($callback = $route->getDefault('_title_callback')) {
      $callable = $this->controllerResolver->getControllerFromDefinition($callback);
      $arguments = $this->controllerResolver->getArguments($request, $callable);
      $route_title = call_user_func_array($callable, $arguments);
    }
    elseif ($title = $route->getDefault('_title')) {
      $options = array();
      if ($context = $route->getDefault('_title_context')) {
        $options['context'] = $context;
      }
      // Fall back to a static string from the route.
      $route_title = $this->translationManager->translate($title, array(), $options);
    }
    return $route_title;
  }

}
