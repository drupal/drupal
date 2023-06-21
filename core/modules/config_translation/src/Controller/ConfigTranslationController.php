<?php

namespace Drupal\config_translation\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\config_translation\Exception\ConfigMapperLanguageException;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
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
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, AccountInterface $account, LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    $this->configMapperManager = $config_mapper_manager;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->account = $account;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
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
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * Language translations overview page for a configuration name.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $plugin_id
   *   The plugin ID of the mapper.
   *
   * @return array
   *   Page render array.
   */
  public function itemPage(Request $request, RouteMatchInterface $route_match, $plugin_id) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($plugin_id);
    $mapper->populateFromRouteMatch($route_match);

    $page = [];
    $page['#title'] = $this->t('Translations for %label', ['%label' => $mapper->getTitle()]);

    $languages = $this->languageManager->getLanguages();
    if (count($languages) == 1) {
      $this->messenger()->addWarning($this->t('In order to translate configuration, the website must have at least two <a href=":url">languages</a>.', [':url' => Url::fromRoute('entity.configurable_language.collection')->toString()]));
    }

    try {
      $original_langcode = $mapper->getLangcode();
      $operations_access = TRUE;
    }
    catch (ConfigMapperLanguageException $exception) {
      $items = [];
      foreach ($mapper->getConfigNames() as $config_name) {
        $langcode = $mapper->getLangcodeFromConfig($config_name);
        $items[] = $this->t('@name: @langcode', [
          '@name' => $config_name,
          '@langcode'  => $langcode,
        ]);
      }
      $message = [
        'message' => ['#markup' => $this->t('The configuration objects have different language codes so they cannot be translated:')],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ];
      $this->messenger()->addWarning($this->renderer->renderPlain($message));

      $original_langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
      $operations_access = FALSE;
    }

    if (!isset($languages[$original_langcode])) {
      // If the language is not configured on the site, create a dummy language
      // object for this listing only to ensure the user gets useful info.
      $language_name = $this->languageManager->getLanguageName($original_langcode);
      $languages[$original_langcode] = new Language(['id' => $original_langcode, 'name' => $language_name]);
    }

    // We create a fake request object to pass into
    // ConfigMapperInterface::populateFromRouteMatch() for the different languages.
    // Creating a separate request for each language and route is neither easily
    // possible nor performant.
    $fake_request = $request->duplicate();

    $page['languages'] = [
      '#type' => 'table',
      '#header' => [$this->t('Language'), $this->t('Operations')],
    ];
    foreach ($languages as $language) {
      $langcode = $language->getId();

      // This is needed because
      // ConfigMapperInterface::getAddRouteParameters(), for example,
      // needs to return the correct language code for each table row.
      $fake_route_match = RouteMatch::createFromRequest($fake_request);
      $mapper->populateFromRouteMatch($fake_route_match);
      $mapper->setLangcode($langcode);

      // Prepare the language name and the operations depending on whether this
      // is the original language or not.
      if ($langcode == $original_langcode) {
        $language_name = '<strong>' . $this->t('@language (original)', ['@language' => $language->getName()]) . '</strong>';

        // Check access for the path/route for editing, so we can decide to
        // include a link to edit or not.
        $edit_access = $this->accessManager->checkNamedRoute($mapper->getBaseRouteName(), $route_match->getRawParameters()->all(), $this->account);

        // Build list of operations.
        $operations = [];
        if ($edit_access) {
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute($mapper->getBaseRouteName(), $mapper->getBaseRouteParameters(), ['query' => ['destination' => $mapper->getOverviewPath()]]),
          ];
        }
      }
      else {
        $language_name = $language->getName();

        $operations = [];
        // If no translation exists for this language, link to add one.
        if (!$mapper->hasTranslation($language)) {
          $operations['add'] = [
            'title' => $this->t('Add'),
            'url' => Url::fromRoute($mapper->getAddRouteName(), $mapper->getAddRouteParameters()),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 880,
              ]),
            ],
          ];
        }
        else {
          // Otherwise, link to edit the existing translation.
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute($mapper->getEditRouteName(), $mapper->getEditRouteParameters()),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 880,
              ]),
            ],
          ];

          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute($mapper->getDeleteRouteName(), $mapper->getDeleteRouteParameters()),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 880,
              ]),
            ],
          ];
        }
      }

      $page['languages'][$langcode]['language'] = [
        '#markup' => $language_name,
      ];

      $page['languages'][$langcode]['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
        // Even if the mapper contains multiple language codes, the source
        // configuration can still be edited.
        '#access' => ($langcode == $original_langcode) || $operations_access,
        '#attached' => ['library' => ['core/drupal.dialog.ajax']],
      ];
    }
    return $page;
  }

}
