<?php

namespace Drupal\Tests\system\Functional;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Seven's handling of link attributes on multi-bundle entity add page.
 *
 * @group system
 */
class SevenBundleAddPageLinkAttributesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Tests that Seven preserves attributes in multi-bundle entity add links.
   */
  public function testSevenBundleAddPageLinkAttributes() {
    $account = $this->drupalCreateUser(['administer entity_test_with_bundle content']);
    $this->drupalLogin($account);

    $this->config('system.theme')->set('default', 'seven')->save();

    for ($i = 0; $i < 2; $i++) {
      EntityTestBundle::create([
        'id' => $this->randomMachineName(),
        'label' => $this->randomString(),
        'description' => $this->randomString(),
      ])->save();
    }

    $this->drupalGet('/entity_test_with_bundle/add');
    $this->assertSession()->elementExists('css', 'a.bundle-link');
  }

}
