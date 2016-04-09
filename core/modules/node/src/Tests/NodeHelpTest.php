<?php

namespace Drupal\node\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests help functionality for nodes.
 *
 * @group node
 */
class NodeHelpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = array('block', 'node', 'help');

  /**
   * The name of the test node type to create.
   *
   * @var string
   */
  protected $testType;

  /**
   * The test 'node help' text to be checked.
   *
   * @var string
   */
  protected $testText;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $admin_user = $this->drupalCreateUser(array(
      'administer content types',
      'administer nodes',
      'bypass node access',
    ));

    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('help_block');

    $this->testType = 'type';
    $this->testText = t('Help text to find on node forms.');

    // Create content type.
    $this->drupalCreateContentType(array(
      'type' => $this->testType,
      'help' => $this->testText,
    ));
  }

  /**
   * Verifies that help text appears on node add/edit forms.
   */
  public function testNodeShowHelpText() {
    // Check the node add form.
    $this->drupalGet('node/add/' . $this->testType);
    $this->assertResponse(200);
    $this->assertText($this->testText);

    // Create node and check the node edit form.
    $node = $this->drupalCreateNode(array('type' => $this->testType));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200);
    $this->assertText($this->testText);
  }
}
