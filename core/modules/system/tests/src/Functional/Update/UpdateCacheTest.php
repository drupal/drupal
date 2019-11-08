<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests caches during updates.
 *
 * @group Update
 */
class UpdateCacheTest extends BrowserTestBase {
  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that caches are cleared during updates.
   *
   * @see \Drupal\Core\Update\UpdateServiceProvider
   * @see \Drupal\Core\Update\UpdateBackend
   */
  public function testCaches() {
    \Drupal::cache()->set('will_not_exist_after_update', TRUE);
    // The site might be broken at the time so logging in using the UI might
    // not work, so we use the API itself.
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);

    // Clicking continue should clear the caches.
    $this->drupalGet(Url::fromRoute('system.db_update', [], ['path_processing' => FALSE]));
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));

    $this->assertFalse(\Drupal::cache()->get('will_not_exist_after_update', FALSE));
  }

}
