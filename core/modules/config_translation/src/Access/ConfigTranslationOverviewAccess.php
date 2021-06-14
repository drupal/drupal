<?php

namespace Drupal\config_translation\Access;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\config_translation\Exception\ConfigMapperLanguageException;
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
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $mapper = $this->getMapperFromRouteMatch($route_match);

    try {
      $langcode = $mapper->getLangcode();
    }
    catch (ConfigMapperLanguageException $exception) {
      // ConfigTranslationController shows a helpful message if the language
      // codes do not match, so do not let that prevent granting access.
      $langcode = 'en';
    }
    $source_language = $this->languageManager->getLanguage($langcode);

    return $this->doCheckAccess($account, $mapper, $source_language);
  }

  /**
   * Gets a configuration mapper using a route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match to populate the mapper with.
   *
   * @return \Drupal\config_translation\ConfigMapperInterface
   *   The configuration mapper.
   */
  protected function getMapperFromRouteMatch(RouteMatchInterface $route_match) {
    $mapper = $this->configMapperManager->createInstance($route_match->getRouteObject()
      ->getDefault('plugin_id'));
    $mapper->populateFromRouteMatch($route_match);
    return $mapper;
  }

  /**
   * Checks access given an account, configuration mapper, and source language.
   *
   * Grants access if the proper permission is granted to the account, the
   * configuration has translatable pieces, and the source language is not
   * locked given it is present.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The configuration mapper to check access for.
   * @param \Drupal\Core\Language\LanguageInterface|null $source_language
   *   The source language to check for, if any.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  protected function doCheckAccess(AccountInterface $account, ConfigMapperInterface $mapper, $source_language = NULL) {
    $access =
      $account->hasPermission('translate configuration') &&
      $mapper->hasSchema() &&
      $mapper->hasTranslatable() &&
      (!$source_language || !$source_language->isLocked());

    return AccessResult::allowedIf($access)->cachePerPermissions();
  }

}
