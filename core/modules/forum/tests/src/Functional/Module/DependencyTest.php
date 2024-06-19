<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Functional\Module;

use Drupal\Tests\system\Functional\Module\ModuleTestBase;

/**
 * Enable module without dependency enabled.
 *
 * @group form
 * @group legacy
 */
class DependencyTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests attempting to uninstall a module that has installed dependents.
   */
  public function testUninstallDependents(): void {
    // Enable the forum module.
    $edit = ['modules[forum][enable]' => 'forum'];
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->submitForm([], 'Continue');
    $this->assertModules(['forum'], TRUE);

    // Check that the comment module cannot be uninstalled.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->fieldDisabled('uninstall[comment]');

    // Delete any forum terms.
    $vid = $this->config('forum.settings')->get('vocabulary');
    // Ensure taxonomy has been loaded into the test-runner after forum was
    // enabled.
    \Drupal::moduleHandler()->load('taxonomy');
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties(['vid' => $vid]);
    $storage->delete($terms);

    // Uninstall the forum module, and check that taxonomy now can also be
    // uninstalled.
    $edit = ['uninstall[forum]' => 'forum'];
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');

    // Uninstall comment module.
    $edit = ['uninstall[comment]' => 'comment'];
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
  }

}
