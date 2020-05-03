<?php

namespace Drupal\Tests\block_content\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\block_content\Entity\BlockContent;
use Drupal\user\UserInterface;

/**
 * Create a block with revisions.
 *
 * @group block_content
 */
class BlockContentRevisionsTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Stores blocks created during the test.
   * @var array
   */
  protected $blocks;

  /**
   * Stores log messages used during the test.
   * @var array
   */
  protected $revisionLogs;

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create initial block.
    $block = $this->createBlockContent('initial');

    $blocks = [];
    $logs = [];

    // Get original block.
    $blocks[] = $block->getRevisionId();
    $logs[] = '';

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $block->setNewRevision(TRUE);
      $block->setRevisionLogMessage($this->randomMachineName(32));
      $block->setRevisionUser($this->adminUser);
      $block->setRevisionCreationTime(REQUEST_TIME);
      $logs[] = $block->getRevisionLogMessage();
      $block->save();
      $blocks[] = $block->getRevisionId();
    }

    $this->blocks = $blocks;
    $this->revisionLogs = $logs;
  }

  /**
   * Checks block revision related operations.
   */
  public function testRevisions() {
    $blocks = $this->blocks;
    $logs = $this->revisionLogs;

    foreach ($blocks as $delta => $revision_id) {
      // Confirm the correct revision text appears.
      /** @var \Drupal\block_content\BlockContentInterface  $loaded */
      $loaded = $this->container->get('entity_type.manager')
        ->getStorage('block_content')
        ->loadRevision($revision_id);
      // Verify revision log is the same.
      $this->assertEqual($loaded->getRevisionLogMessage(), $logs[$delta], new FormattableMarkup('Correct log message found for revision @revision', [
        '@revision' => $loaded->getRevisionId(),
      ]));
      if ($delta > 0) {
        $this->assertInstanceOf(UserInterface::class, $loaded->getRevisionUser());
        $this->assertIsNumeric($loaded->getRevisionUserId());
        $this->assertIsNumeric($loaded->getRevisionCreationTime());
      }
    }

    // Confirm that this is the default revision.
    $this->assertTrue($loaded->isDefaultRevision(), 'Third block revision is the default one.');

    // Make a new revision and set it to not be default.
    // This will create a new revision that is not "front facing".
    // Save this as a non-default revision.
    $loaded->setNewRevision();
    $loaded->isDefaultRevision(FALSE);
    $loaded->body = $this->randomMachineName(8);
    $loaded->save();

    $this->drupalGet('block/' . $loaded->id());
    $this->assertNoText($loaded->body->value, 'Revision body text is not present on default version of block.');

    // Verify that the non-default revision id is greater than the default
    // revision id.
    $default_revision = BlockContent::load($loaded->id());
    $this->assertTrue($loaded->getRevisionId() > $default_revision->getRevisionId(), 'Revision id is greater than default revision id.');
  }

}
