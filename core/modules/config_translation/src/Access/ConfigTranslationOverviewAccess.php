<?php

/**
 * @file
 * Contains \Drupal\config_translation\Access\ConfigTranslationOverviewAccess.
 */

namespace Drupal\config_translation\Access;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying the configuration translation overview.
 */
class ConfigTranslationOverviewAccess implements AccessInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The source language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $sourceLanguage;

  /**
   * Constructs a ConfigTranslationOverviewAccess object.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager, LanguageManagerInterface $language_manager) {
    $this->configMapperManager = $config_mapper_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Checks access to the overview based on permissions and translatability.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route_match to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $route = $route_match->getRouteObject();

    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($route->getDefault('plugin_id'));
    $mapper->populateFromRouteMatch($route_match);
    $this->sourceLanguage = $this->languageManager->getLanguage($mapper->getLangcode());

    // Allow access to the translation overview if the proper permission is
    // granted, the configuration has translatable pieces, and the source
    // language is not locked if it is present.
    $source_language_access = is_null($this->sourceLanguage) || !$this->sourceLanguage->isLocked();
    $access =
      $account->hasPermission('translate configuration') &&
      $mapper->hasSchema() &&
      $mapper->hasTranslatable() &&
      $source_language_access;

    return AccessResult::allowedIf($access)->cachePerPermissions();
  }

}
