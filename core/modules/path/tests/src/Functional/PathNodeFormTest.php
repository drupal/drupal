<?php

namespace Drupal\Tests\path\Functional;

/**
 * Tests the Path Node form UI.
 *
 * @group path
 */
class PathNodeFormTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'path'];

  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(['create page content', 'create url aliases']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node form ui.
   */
  public function testNodeForm() {
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/page');

    // Make sure we have a vertical tab fieldset and 'Path' fields.
    $assert_session->elementContains('css', '.form-type-vertical-tabs #edit-path-0 summary', 'URL alias');
    $assert_session->fieldExists('path[0][alias]');

    // Disable the 'Path' field for this content type.
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')
      ->removeComponent('path')
      ->save();

    $this->drupalGet('node/add/page');

    // See if the whole fieldset is gone now.
    $assert_session->elementNotExists('css', '.form-type-vertical-tabs #edit-path-0');
    $assert_session->fieldNotExists('path[0][alias]');
  }

}
