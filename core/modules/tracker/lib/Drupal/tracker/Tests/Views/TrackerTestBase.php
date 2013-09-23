<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\Views\TrackerTestBase.
 */

namespace Drupal\tracker\Tests\Views;

use Drupal\Core\Language\Language;
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

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('tracker_test_views'));

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $permissions = array('access comments', 'create page content', 'post comments', 'skip comment approval');
    $account = $this->drupalCreateUser($permissions);

    $this->drupalLogin($account);

    $this->node = $this->drupalCreateNode(array(
      'title' => $this->randomName(8),
      'uid' => $account->id(),
      'status' => 1,
    ));

    $this->comment = entity_create('comment', array(
      'nid' => $this->node->id(),
      'subject' => $this->randomName(),
      'comment_body[' . Language::LANGCODE_NOT_SPECIFIED . '][0][value]' => $this->randomName(20),
    ));

  }

}
