<?php

declare(strict_types=1);

namespace Drupal\node_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for node_test.
 */
class NodeTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_view() for node entities.
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    if ($node->isNew()) {
      return;
    }
    if ($view_mode == 'rss') {
      // Add RSS elements and namespaces when building the RSS feed.
      $node->rss_elements[] = [
        'key' => 'testElement',
        'value' => t('Value of testElement RSS element for node @nid.', [
          '@nid' => $node->id(),
        ]),
      ];
      // Add content that should be displayed only in the RSS feed.
      $build['extra_feed_content'] = [
        '#markup' => '<p>' . t('Extra data that should appear only in the RSS feed for node @nid.', [
          '@nid' => $node->id(),
        ]) . '</p>',
        '#weight' => 10,
      ];
    }
    if ($view_mode != 'rss') {
      // Add content that should NOT be displayed in the RSS feed.
      $build['extra_non_feed_content'] = [
        '#markup' => '<p>' . t('Extra data that should appear everywhere except the RSS feed for node @nid.', [
          '@nid' => $node->id(),
        ]) . '</p>',
      ];
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_build_defaults_alter() for node entities.
   */
  #[Hook('node_build_defaults_alter')]
  public function nodeBuildDefaultsAlter(array &$build, NodeInterface &$node, $view_mode = 'full'): void {
    if ($view_mode == 'rss') {
      $node->rss_namespaces['xmlns:test'] = 'http://example.com/test-namespace';
    }
  }

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants(AccountInterface $account, $operation) {
    // Give everyone full grants so we don't break other node tests.
    // Our node access tests asserts three realms of access.
    // See testGrantAlter().
    return ['test_article_realm' => [1], 'test_page_realm' => [1], 'test_alter_realm' => [2]];
  }

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node) {
    // Return nothing when testing for empty responses.
    if (!empty($node->disable_node_access)) {
      return;
    }
    $grants = [];
    if ($node->getType() == 'article') {
      // Create grant in arbitrary article_realm for article nodes.
      $grants[] = [
        'realm' => 'test_article_realm',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }
    elseif ($node->getType() == 'page') {
      // Create grant in arbitrary page_realm for page nodes.
      $grants[] = [
        'realm' => 'test_page_realm',
        'gid' => 1,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];
    }
    return $grants;
  }

  /**
   * Implements hook_node_access_records_alter().
   */
  #[Hook('node_access_records_alter')]
  public function nodeAccessRecordsAlter(&$grants, NodeInterface $node): void {
    if (!empty($grants)) {
      foreach ($grants as $key => $grant) {
        // Alter grant from test_page_realm to test_alter_realm and modify the gid.
        if ($grant['realm'] == 'test_page_realm' && $node->isPromoted()) {
          $grants[$key]['realm'] = 'test_alter_realm';
          $grants[$key]['gid'] = 2;
        }
      }
    }
  }

  /**
   * Implements hook_node_grants_alter().
   */
  #[Hook('node_grants_alter')]
  public function nodeGrantsAlter(&$grants, AccountInterface $account, $operation): void {
    // Return an empty array of grants to prove that we can alter by reference.
    $grants = [];
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for node entities.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $node) {
    if ($node->getTitle() == 'testing_node_presave') {
      // Sun, 19 Nov 1978 05:00:00 GMT
      $node->setCreatedTime(280299600);
      // Drupal 1.0 release.
      $node->changed = 979534800;
    }
    // Determine changes.
    if (!empty($node->original) && $node->original->getTitle() == 'test_changes') {
      if ($node->original->getTitle() != $node->getTitle()) {
        $node->title->value .= '_presave';
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for node entities.
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node) {
    // Determine changes on update.
    if (!empty($node->original) && $node->original->getTitle() == 'test_changes') {
      if ($node->original->getTitle() != $node->getTitle()) {
        $node->title->value .= '_update';
      }
    }
  }

  /**
   * Implements hook_entity_view_mode_alter().
   */
  #[Hook('entity_view_mode_alter')]
  public function entityViewModeAlter(&$view_mode, EntityInterface $entity): void {
    // Only alter the view mode if we are on the test callback.
    $change_view_mode = \Drupal::state()->get('node_test_change_view_mode', '');
    if ($change_view_mode) {
      $view_mode = $change_view_mode;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_insert() for node entities.
   *
   * This tests saving a node on node insert.
   *
   * @see \Drupal\node\Tests\NodeSaveTest::testNodeSaveOnInsert()
   */
  #[Hook('node_insert')]
  public function nodeInsert(NodeInterface $node) {
    // Set the node title to the node ID and save.
    if ($node->getTitle() == 'new') {
      $node->setTitle('Node ' . $node->id());
      $node->setNewRevision(FALSE);
      $node->save();
    }
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    if (!$form_state->get('node_test_form_alter')) {
      \Drupal::messenger()->addStatus('Storage is not set');
      $form_state->set('node_test_form_alter', TRUE);
    }
    else {
      \Drupal::messenger()->addStatus('Storage is set');
    }
  }

}
