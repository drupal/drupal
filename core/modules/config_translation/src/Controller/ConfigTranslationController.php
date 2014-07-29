<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationController.
 */

namespace Drupal\config_translation\Controller;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Provides page callbacks for the configuration translation interface.
 */
class ConfigTranslationController extends ControllerBase {

  /**
   * The configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The path processor service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The language manager.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * Constructs a ConfigTranslationController.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, AccountInterface $account, LanguageManagerInterface $language_manager) {
    $this->configMapperManager = $config_mapper_manager;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->account = $account;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('access_manager'),
      $container->get('router'),
      $container->get('path_processor_manager'),
      $container->get('current_user'),
      $container->get('language_manager')
    );
  }

  /**
   * Language translations overview page for a configuration name.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param string $plugin_id
   *   The plugin ID of the mapper.
   *
   * @return array
   *   Page render array.
   */
  public function itemPage(Request $request, $plugin_id) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRequest($request);

    $page = array();
    $page['#title'] = $this->t('Translations for %label', array('%label' => $mapper->getTitle()));

    // It is possible the original language this configuration was saved with is
    // not on the system. For example, the configuration shipped in English but
    // the site has no English configured. Represent the original language in
    // the table even if it is not currently configured.
    $languages = $this->languageManager->getLanguages();
    $original_langcode = $mapper->getLangcode();
    if (!isset($languages[$original_langcode])) {
      $language_name = $this->languageManager->getLanguageName($original_langcode);
      if ($original_langcode == 'en') {
        $language_name = $this->t('Built-in English');
      }
      // Create a dummy language object for this listing only.
      $languages[$original_langcode] = new Language(array('id' => $original_langcode, 'name' => $language_name));
    }

    // We create a fake request object to pass into
    // ConfigMapperInterface::populateFromRequest() for the different languages.
    // Creating a separate request for each language and route is neither easily
    // possible nor performant.
    $fake_request = $request->duplicate();

    $page['languages'] = array(
      '#type' => 'table',
      '#header' => array($this->t('Language'), $this->t('Operations')),
    );
    foreach ($languages as $language) {
      $langcode = $language->id;

      // This is needed because
      // ConfigMapperInterface::getAddRouteParameters(), for example,
      // needs to return the correct language code for each table row.
      $fake_request->attributes->set('langcode', $langcode);
      $mapper->populateFromRequest($fake_request);

      // Prepare the language name and the operations depending on whether this
      // is the original language or not.
      if ($langcode == $original_langcode) {
        $language_name = '<strong>' . $this->t('@language (original)', array('@language' => $language->name)) . '</strong>';

        // Check access for the path/route for editing, so we can decide to
        // include a link to edit or not.
        $route_request = $this->getRequestForPath($request, $mapper->getBasePath());
        $edit_access = FALSE;
        if (!empty($route_request)) {
          $route_name = $route_request->attributes->get(RouteObjectInterface::ROUTE_NAME);
          // Note that the parameters don't really matter here since we're
          // passing in the request which already has the upcast attributes.
          $parameters = array();
          $edit_access = $this->accessManager->checkNamedRoute($route_name, $parameters, $this->account, $route_request);
        }

        // Build list of operations.
        $operations = array();
        if ($edit_access) {
          $operations['edit'] = array(
            'title' => $this->t('Edit'),
            'route_name' => $mapper->getBaseRouteName(),
            'route_parameters' => $mapper->getBaseRouteParameters(),
            'query' => array('destination' => $mapper->getOverviewPath()),
          );
        }
      }
      else {
        $language_name = $language->name;

        $operations = array();
        // If no translation exists for this language, link to add one.
        if (!$mapper->hasTranslation($language)) {
          $operations['add'] = array(
            'title' => $this->t('Add'),
            'route_name' => $mapper->getAddRouteName(),
            'route_parameters' => $mapper->getAddRouteParameters(),
          );
        }
        else {
          // Otherwise, link to edit the existing translation.
          $operations['edit'] = array(
            'title' => $this->t('Edit'),
            'route_name' => $mapper->getEditRouteName(),
            'route_parameters' => $mapper->getEditRouteParameters(),
          );

          $operations['delete'] = array(
            'title' => $this->t('Delete'),
            'route_name' => $mapper->getDeleteRouteName(),
            'route_parameters' => $mapper->getDeleteRouteParameters(),
          );
        }
      }

      $page['languages'][$langcode]['language'] = array(
        '#markup' => $language_name,
      );

      $page['languages'][$langcode]['operations'] = array(
        '#type' => 'operations',
        '#links' => $operations,
      );
    }
    return $page;
  }

  /**
   * Matches a path in the router.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param string $path
   *   Path to look up.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   A populated request object or NULL if the patch could not be matched.
   */
  protected function getRequestForPath(Request $request, $path) {
    // @todo Use RequestHelper::duplicate once https://drupal.org/node/2090293
    //   is fixed.
    $route_request = Request::create($request->getBaseUrl() . '/' . $path);
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $route_request);
    $route_request->attributes->set('_system_path', $processed);
    // Attempt to match this path to provide a fully built request.
    try {
      $route_request->attributes->add($this->router->matchRequest($route_request));
      return $route_request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
  }

}
