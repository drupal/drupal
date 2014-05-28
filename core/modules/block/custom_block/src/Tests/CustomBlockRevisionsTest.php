<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockRevisionsTest.
 */

namespace Drupal\custom_block\Tests;

/**
 * Tests the block revision functionality.
 */
class CustomBlockRevisionsTest extends CustomBlockTestBase {

  /**
   * Stores blocks created during the test.
   * @var array
   */
  protected $blocks;

  /**
   * Stores log messages used during the test.
   * @var array
   */
  protected $logs;

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block revisions',
      'description' => 'Create a block with revisions.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();

    // Create initial block.
    $block = $this->createCustomBlock('initial');

    $blocks = array();
    $logs = array();

    // Get original block.
    $blocks[] = $block->getRevisionId();
    $logs[] = '';

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $block->setNewRevision(TRUE);
      $block->setRevisionLog($this->randomName(32));
      $logs[] = $block->getRevisionLog();
      $block->save();
      $blocks[] = $block->getRevisionId();
    }

    $this->blocks = $blocks;
    $this->logs = $logs;
  }

  /**
   * Checks block revision related operations.
   */
  public function testRevisions() {
    $blocks = $this->blocks;
    $logs = $this->logs;

    foreach ($blocks as $delta => $revision_id) {
      // Confirm the correct revision text appears.
      $loaded = entity_revision_load('custom_block', $revision_id);
      // Verify log is the same.
      $this->assertEqual($loaded->getRevisionLog(), $logs[$delta], format_string('Correct log message found for revision !revision', array(
        '!revision' => $loaded->getRevisionId(),
      )));
    }

    // Confirm that this is the default revision.
    $this->assertTrue($loaded->isDefaultRevision(), 'Third block revision is the default one.');

    // Make a new revision and set it to not be default.
    // This will create a new revision that is not "front facing".
    // Save this as a non-default revision.
    $loaded->setNewRevision();
    $loaded->isDefaultRevision(FALSE);
    $loaded->body = $this->randomName(8);
    $loaded->save();

    $this->drupalGet('block/' . $loaded->id());
    $this->assertNoText($loaded->body->value, 'Revision body text is not present on default version of block.');

    // Verify that the non-default revision id is greater than the default
    // revision id.
    $default_revision = entity_load('custom_block', $loaded->id());
    $this->assertTrue($loaded->getRevisionId() > $default_revision->getRevisionId(), 'Revision id is greater than default revision id.');
  }

}
