<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the views bulk form with batch action.
 *
 * @group action
 * @see \Drupal\system\Plugin\views\field\BulkForm
 */
class UserBatchActionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'user_batch_action_test'];

  /**
   * Tests user admin batch.
   */
  public function testUserAction() {
    $this->drupalLogin($this->rootUser);

    $themes = ['classy', 'seven', 'bartik'];

    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install($themes);

    foreach ($themes as $theme) {
      \Drupal::configFactory()->getEditable('system.theme')->set('default', $theme)->save();
      $this->drupalGet('admin/people');
      $edit = [
        'user_bulk_form[0]' => TRUE,
        'action' => 'user_batch_action_test_action',
      ];
      $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
      $this->checkForMetaRefresh();
      $this->assertSession()->pageTextContains('One item has been processed.');
    }
  }

}
