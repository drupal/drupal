<?php

/**
 * @file
 * Contains \Drupal\config_translation\Access\ConfigTranslationOverviewAccess.
 */

namespace Drupal\config_translation\Access;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

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
   * The source language.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $sourceLanguage;

  /**
   * Constructs a ConfigTranslationOverviewAccess object.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager) {
    $this->configMapperManager = $config_mapper_manager;
  }

  /**
   * Checks access to the overview based on permissions and translatability.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($route->getDefault('plugin_id'));
    $mapper->populateFromRequest($request);

    $this->sourceLanguage = $mapper->getLanguageWithFallback();

    // Allow access to the translation overview if the proper permission is
    // granted, the configuration has translatable pieces, and the source
    // language is not locked.
    $access =
      $account->hasPermission('translate configuration') &&
      $mapper->hasSchema() &&
      $mapper->hasTranslatable() &&
      !$this->sourceLanguage->locked;

    return $access ? static::ALLOW : static::DENY;
  }

}
