<?php

  /**
   * @file
   * Contains \Drupal\views\Tests\UI\DisplayAttachmentTest.
   */

namespace Drupal\views\Tests\UI;

/**
 * Tests the UI for the attachment display plugin.
 *
 * @see Drupal\views\Plugin\views\display\Attachment
 */
class DisplayAttachmentTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_attachment_ui');

  public static function getInfo() {
    return array(
      'name' => 'Display: Attachment',
      'description' => 'Tests the UI for the attachment display plugin.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests the attachment UI.
   */
  public function testAttachmentUI() {
    $this->drupalGet('admin/structure/views/view/test_attachment_ui/edit/attachment_1');
    $this->assertText(t('Not defined'), 'The right text appears if there is no attachment selection yet.');

    $this->drupalGet('admin/structure/views/nojs/display/test_attachment_ui/attachment_1/displays');

    foreach (array('default', 'page-1') as $display_id) {
      $this->assertNoFieldChecked("edit-displays-$display_id", format_string('Make sure the @display_id can be marked as attached', array('@display_id' => $display_id)));
    }

    // Save the attachments and test the value on the view.
    $this->drupalPost(NULL, array('displays[default]' => 1, 'displays[page_1]' => 1), t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view('test_attachment_ui');
    $view->initDisplay();
    $this->assertEqual(array_keys($view->displayHandlers->get('attachment_1')->getOption('displays')), array('default', 'page_1'), 'The attached displays got saved as expected');
  }
}
