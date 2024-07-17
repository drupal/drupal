<?php

namespace Drupal\config_translation\Access;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\config_translation\Exception\ConfigMapperLanguageException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for displaying the translation add, edit, and delete forms.
 */
class ConfigTranslationFormAccess extends ConfigTranslationOverviewAccess {

  /**
   * Checks access to the overview based on permissions and translatability.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route_match to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param string $langcode
   *   The language code of the target language.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $langcode = NULL) {
    $mapper = $this->getMapperFromRouteMatch($route_match);

    try {
      $source_langcode = $mapper->getLangcode();
      $source_language = $this->languageManager->getLanguage($source_langcode);

      $target_language = $this->languageManager->getLanguage($langcode);

      return $this->doCheckAccess($account, $mapper, $source_language, $target_language);
    }
    catch (ConfigMapperLanguageException) {
      return AccessResult::forbidden();
    }
  }

  /**
   * Checks access given an account, configuration mapper, and source language.
   *
   * In addition to the checks performed by
   * ConfigTranslationOverviewAccess::doCheckAccess() this makes sure the target
   * language is not locked and the target language is not the source language.
   *
   * Although technically configuration can be overlaid with translations in the
   * same language, that is logically not a good idea.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\config_translation\ConfigMapperInterface $mapper
   *   The configuration mapper to check access for.
   * @param \Drupal\Core\Language\LanguageInterface|null $source_language
   *   The source language to check for, if any.
   * @param \Drupal\Core\Language\LanguageInterface|null $target_language
   *   The target language to check for, if any.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   *
   * @see \Drupal\config_translation\Access\ConfigTranslationOverviewAccess::doCheckAccess()
   */
  protected function doCheckAccess(AccountInterface $account, ConfigMapperInterface $mapper, $source_language = NULL, $target_language = NULL) {
    $base_access_result = parent::doCheckAccess($account, $mapper, $source_language);

    $access =
      $target_language &&
      !$target_language->isLocked() &&
      (!$source_language || ($target_language->getId() !== $source_language->getId()));

    return $base_access_result->andIf(AccessResult::allowedIf($access));

  }

}
