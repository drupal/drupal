<?php

/**
 * @file
 * Contains \Drupal\config_translation\Access\ConfigTranslationFormAccess.
 */

namespace Drupal\config_translation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying the translation add, edit, and delete forms.
 */
class ConfigTranslationFormAccess extends ConfigTranslationOverviewAccess {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    // For the translation forms we have a target language, so we need some
    // checks in addition to the checks performed for the translation overview.
    $base_access = parent::access($route, $request, $account);
    if ($base_access->isAllowed()) {
      $target_language = language_load($request->attributes->get('langcode'));

      // Make sure that the target language is not locked, and that the target
      // language is not the original submission language. Although technically
      // configuration can be overlaid with translations in the same language,
      // that is logically not a good idea.
      $access =
        !empty($target_language) &&
        !$target_language->locked &&
        $target_language->id != $this->sourceLanguage->id;

      return $base_access->andIf(AccessResult::allowedIf($access));
    }
    return $base_access;
  }

}
