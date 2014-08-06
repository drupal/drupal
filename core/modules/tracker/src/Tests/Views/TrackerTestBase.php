<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\Views\TrackerTestBase.
 */

namespace Drupal\tracker\Tests\Views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Base class for all tracker tests.
 */
abstract class TrackerTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'tracker', 'tracker_test_views');

  /**
   * The node used for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('tracker_test_views'));

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    // Add a comment field.
    $this->container->get('comment.manager')->addDefaultField('node', 'page');

    $permissions = array('access comments', 'create page content', 'post comments', 'skip comment approval');
    $account = $this->drupalCreateUser($permissions);

    $this->drupalLogin($account);

    $this->node = $this->drupalCreateNode(array(
      'title' => $this->randomMachineName(8),
      'uid' => $account->id(),
      'status' => 1,
    ));

    $this->comment = entity_create('comment', array(
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => $this->randomMachineName(),
      'comment_body[' . LanguageInterface::LANGCODE_NOT_SPECIFIED . '][0][value]' => $this->randomMachineName(20),
    ));

  }

}
