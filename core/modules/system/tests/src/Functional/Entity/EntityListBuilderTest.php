<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests entity list builder functionality.
 *
 * @group Entity
 */
class EntityListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $this->webUser = $this->drupalCreateUser([
      'administer entity_test content',
    ]);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Test paging.
   */
  public function testPager() {
    // Create 51 test entities.
    for ($i = 1; $i < 52; $i++) {
      EntityTest::create(['name' => 'Test entity ' . $i])->save();
    }

    // Load the listing page.
    $this->drupalGet('entity_test/list');

    // Item 51 should not be present.
    $this->assertRaw('Test entity 50', 'Item 50 is shown.');
    $this->assertNoRaw('Test entity 51', 'Item 51 is on the next page.');

    // Browse to the next page.
    $this->clickLink(t('Page 2'));
    $this->assertNoRaw('Test entity 50', 'Test entity 50 is on the previous page.');
    $this->assertRaw('Test entity 51', 'Test entity 51 is shown.');
  }

  /**
   * Tests that the correct cache contexts are set.
   */
  public function testCacheContexts() {
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity.manager')->getListBuilder('entity_test');

    $build = $list_builder->render();
    $this->container->get('renderer')->renderRoot($build);

    $this->assertEqual(['entity_test_view_grants', 'languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'url.query_args.pagers:0', 'user.permissions'], $build['#cache']['contexts']);
  }

  /**
   * Tests if the list cache tags are set.
   */
  public function testCacheTags() {
    $this->drupalGet('entity_test/list');
    $this->assertCacheTag('entity_test_list');
  }

}
