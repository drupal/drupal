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
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $this->webUser = $this->drupalCreateUser([
      'administer entity_test content',
    ]);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests paging.
   */
  public function testPager() {
    // Create 51 test entities.
    for ($i = 1; $i < 52; $i++) {
      EntityTest::create(['name' => 'Test entity ' . $i])->save();
    }

    // Load the listing page.
    $this->drupalGet('entity_test/list');

    // Item 51 should not be present.
    $this->assertRaw('Test entity 50');
    $this->assertSession()->responseNotContains('Test entity 51');

    // Browse to the next page, test entity 51 is shown.
    $this->clickLink('Page 2');
    $this->assertSession()->responseNotContains('Test entity 50');
    $this->assertRaw('Test entity 51');
  }

  /**
   * Tests that the correct cache contexts are set.
   */
  public function testCacheContexts() {
    /** @var \Drupal\Core\Entity\EntityListBuilderInterface $list_builder */
    $list_builder = $this->container->get('entity_type.manager')->getListBuilder('entity_test');

    $build = $list_builder->render();
    $this->container->get('renderer')->renderRoot($build);

    $this->assertEquals(['entity_test_view_grants', 'languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'url.query_args.pagers:0', 'user.permissions'], $build['#cache']['contexts']);
  }

  /**
   * Tests if the list cache tags are set.
   */
  public function testCacheTags() {
    $this->drupalGet('entity_test/list');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'entity_test_list');
  }

}
