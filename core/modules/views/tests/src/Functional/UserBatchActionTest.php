<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the views bulk form with batch action.
 *
 * @group action
 * @see \Drupal\action\Plugin\views\field\BulkForm
 */
class UserBatchActionTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['user', 'user_batch_action_test', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests user admin batch.
   */
  public function testUserAction() {
    $themes = ['classy', 'seven', 'bartik', 'test_subseven'];
    $this->container->get('theme_installer')->install($themes);

    $this->drupalLogin($this->rootUser);

    foreach ($themes as $theme) {
      $this->config('system.theme')->set('default', $theme)->save();
      $this->drupalGet('admin/people');
      $edit = [
        'user_bulk_form[0]' => TRUE,
        'action' => 'user_batch_action_test_action',
      ];
      $this->drupalPostForm(NULL, $edit, t('Apply'));
      $this->assertSession()->pageTextContains('One item has been processed.');
      $this->assertSession()->pageTextContains($theme . ' theme used');
    }
  }

}
