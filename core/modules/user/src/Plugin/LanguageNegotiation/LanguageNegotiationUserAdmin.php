<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin.
 */

namespace Drupal\user\Plugin\LanguageNegotiation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Identifies admin language from the user preferences.
 *
 * @Plugin(
 *   id = Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   weight = 10,
 *   name = @Translation("Account administration pages"),
 *   description = @Translation("Account administration pages language setting.")
 * )
 */
class LanguageNegotiationUserAdmin extends LanguageNegotiationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-user-admin';

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The router.
   *
   * This is only used when called from an event subscriber, before the request
   * has been populated with the route info.
   *
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface
   */
  protected $router;

  /**
   * Constructs a new LanguageNegotiationUserAdmin instance.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $router
   *   The router.
   */
  public function __construct(AdminContext $admin_context, UrlMatcherInterface $router) {
    $this->adminContext = $admin_context;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('router.admin_context'),
      $container->get('router')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    // User preference (only for authenticated users).
    if ($this->languageManager && $this->currentUser->isAuthenticated() && $this->isAdminPath($request)) {
      $preferred_admin_langcode = $this->currentUser->getPreferredAdminLangcode();
      $default_langcode = $this->languageManager->getDefaultLanguage()->id;
      $languages = $this->languageManager->getLanguages();
      if (!empty($preferred_admin_langcode) && $preferred_admin_langcode != $default_langcode && isset($languages[$preferred_admin_langcode])) {
        $langcode = $preferred_admin_langcode;
      }
    }

    // No language preference from the user or not on an admin path.
    return $langcode;
  }

  /**
   * Checks whether the given path is an administrative one.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE if the path is administrative, FALSE otherwise.
   */
  public function isAdminPath(Request $request) {
    $result = FALSE;
    if ($request && $this->adminContext) {
      // If called from an event subscriber, the request may not the route info
      // yet, so use the router to look up the path first.
      if (!$route_object = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
        $attributes = $this->router->match('/' . urldecode(trim($request->getPathInfo(), '/')));
        $route_object = $attributes[RouteObjectInterface::ROUTE_OBJECT];
      }
      $result = $this->adminContext->isAdminRoute($route_object);
    }
    return $result;
  }

}
