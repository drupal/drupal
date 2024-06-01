<?php

namespace Drupal\user\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\language\Attribute\LanguageNegotiation;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Identifies admin language from the user preferences.
 */
#[LanguageNegotiation(
  id: LanguageNegotiationUserAdmin::METHOD_ID,
  name: new TranslatableMarkup('Account administration pages'),
  types: [LanguageInterface::TYPE_INTERFACE],
  weight: -10,
  description: new TranslatableMarkup('Account administration pages language setting.')
)]
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
   * The path processor manager.
   *
   * @var \Drupal\Core\PathProcessor\PathProcessorManager
   */
  protected $pathProcessorManager;

  /**
   * The stacked route match.
   *
   * @var \Drupal\Core\Routing\StackedRouteMatchInterface
   */
  protected $stackedRouteMatch;

  /**
   * Constructs a new LanguageNegotiationUserAdmin instance.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $router
   *   The router.
   * @param \Drupal\Core\PathProcessor\PathProcessorManager $path_processor_manager
   *   The path processor manager.
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $stacked_route_match
   *   The stacked route match.
   */
  public function __construct(AdminContext $admin_context, UrlMatcherInterface $router, PathProcessorManager $path_processor_manager, StackedRouteMatchInterface $stacked_route_match) {
    $this->adminContext = $admin_context;
    $this->router = $router;
    $this->pathProcessorManager = $path_processor_manager;
    $this->stackedRouteMatch = $stacked_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('router.admin_context'),
      $container->get('router'),
      $container->get('path_processor_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(?Request $request = NULL) {
    $langcode = NULL;

    // User preference (only for administrators).
    if (($this->currentUser->hasPermission('access administration pages') || $this->currentUser->hasPermission('view the administration theme')) && ($preferred_admin_langcode = $this->currentUser->getPreferredAdminLangcode(FALSE)) && $this->isAdminPath($request)) {
      $langcode = $preferred_admin_langcode;
    }

    // Not an admin, no admin language preference or not on an admin path.
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
  protected function isAdminPath(Request $request) {
    $result = FALSE;
    if ($request && $this->adminContext) {
      // If called from an event subscriber, the request may not have the route
      // object yet (it is still being built), so use the router to look up
      // based on the path.
      $route_match = $this->stackedRouteMatch->getRouteMatchFromRequest($request);
      if ($route_match && !$route_object = $route_match->getRouteObject()) {
        try {
          // Some inbound path processors make changes to the request. Make a
          // copy as we're not actually routing the request so we do not want to
          // make changes.
          $cloned_request = clone $request;
          // Process the path as an inbound path. This will remove any language
          // prefixes and other path components that inbound processing would
          // clear out, so we can attempt to load the route clearly.
          $path = $this->pathProcessorManager->processInbound(urldecode(rtrim($cloned_request->getPathInfo(), '/')), $cloned_request);
          $attributes = $this->router->match($path);
        }
        catch (ExceptionInterface | HttpException) {
          return FALSE;
        }
        $route_object = $attributes[RouteObjectInterface::ROUTE_OBJECT];
      }
      $result = $this->adminContext->isAdminRoute($route_object);
    }
    return $result;
  }

}
