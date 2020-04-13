<?php

namespace Drupal\Tests\node\Functional;

/**
 * Tests if the syndicate block is available.
 *
 * @group node
 */
class NodeSyndicateBlockTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create a user and log in.
    $admin_user = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the "Syndicate" block is shown when enabled.
   */
  public function testSyndicateBlock() {
    // Place the "Syndicate" block and confirm that it is rendered.
    $this->drupalPlaceBlock('node_syndicate_block', ['id' => 'test_syndicate_block']);
    $this->drupalGet('');
    $this->assertFieldByXPath('//div[@id="block-test-syndicate-block"]/*', NULL, 'Syndicate block found.');
  }

}
