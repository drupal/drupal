<?php

namespace Drupal\history\Hook;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for history.
 */
class HistoryHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.history':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The History module keeps track of which content a user has read. It marks content as <em>new</em> or <em>updated</em> depending on the last time the user viewed it. History records that are older than one month are removed during cron, which means that content older than one month is always considered <em>read</em>. The History module does not have a user interface but it provides a filter to <a href=":views-help">Views</a> to show new or updated content. For more information, see the <a href=":url">online documentation for the History module</a>.', [
          ':views-help' => \Drupal::moduleHandler()->moduleExists('views') ? Url::fromRoute('help.page', [
            'name' => 'views',
          ])->toString() : '#',
          ':url' => 'https://www.drupal.org/documentation/modules/history',
        ]) . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    \Drupal::database()->delete('history')->condition('timestamp', HISTORY_READ_LIMIT, '<')->execute();
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter() for node entities.
   */
  #[Hook('node_view_alter')]
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display): void {
    if ($node->isNew() || isset($node->in_preview)) {
      return;
    }
    // Update the history table, stating that this user viewed this node.
    if ($display->getOriginalMode() === 'full') {
      $build['#cache']['contexts'][] = 'user.roles:authenticated';
      if (\Drupal::currentUser()->isAuthenticated()) {
        // When the window's "load" event is triggered, mark the node as read.
        // This still allows for Drupal behaviors (which are triggered on the
        // "DOMContentReady" event) to add "new" and "updated" indicators.
        $build['#attached']['library'][] = 'history/mark-as-read';
        $build['#attached']['drupalSettings']['history']['nodesToMarkAsRead'][$node->id()] = TRUE;
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for node entities.
   */
  #[Hook('node_delete')]
  public function nodeDelete(EntityInterface $node): void {
    \Drupal::database()->delete('history')->condition('nid', $node->id())->execute();
  }

  /**
   * Implements hook_user_cancel().
   */
  #[Hook('user_cancel')]
  public function userCancel($edit, UserInterface $account, $method): void {
    switch ($method) {
      case 'user_cancel_reassign':
        \Drupal::database()->delete('history')->condition('uid', $account->id())->execute();
        break;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user entities.
   */
  #[Hook('user_delete')]
  public function userDelete($account): void {
    \Drupal::database()->delete('history')->condition('uid', $account->id())->execute();
  }

}
