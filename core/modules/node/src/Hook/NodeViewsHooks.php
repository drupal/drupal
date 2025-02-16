<?php

namespace Drupal\node\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Analyzer;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;
use Drupal\views\ViewExecutable;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node.
 */
class NodeViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_analyze().
   */
  #[Hook('views_analyze')]
  public function viewsAnalyze(ViewExecutable $view): array {
    $ret = [];
    // Check for something other than the default display:
    if ($view->storage->get('base_table') == 'node') {
      foreach ($view->displayHandlers as $display) {
        if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
          // Check for no access control
          $access = $display->getOption('access');
          if (empty($access['type']) || $access['type'] == 'none') {
            $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
            $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access content');
            $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
            $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access content');
            if (!$anonymous_has_access || !$authenticated_has_access) {
              $ret[] = Analyzer::formatMessage($this->t('Some roles lack permission to access content, but display %display has no access control.', ['%display' => $display->display['display_title']]), 'warning');
            }
            $filters = $display->getOption('filters');
            foreach ($filters as $filter) {
              if ($filter['table'] == 'node' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
                continue 2;
              }
            }
            $ret[] = Analyzer::formatMessage($this->t('Display %display has no access control but does not contain a filter for published nodes.', ['%display' => $display->display['display_title']]), 'warning');
          }
        }
      }
    }
    foreach ($view->displayHandlers as $display) {
      if ($display->getPluginId() == 'page') {
        if ($display->getOption('path') == 'node/%') {
          $ret[] = Analyzer::formatMessage($this->t('Display %display has set node/% as path. This will not produce what you want. If you want to have multiple versions of the node view, use Layout Builder.', ['%display' => $display->display['display_title']]), 'warning');
        }
      }
    }
    return $ret;
  }

}
