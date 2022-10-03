<?php

namespace Drupal\Tests\views\Functional;

/**
 * Tests hook_form_BASE_FORM_ID_alter for a ViewsForm.
 *
 * @group views
 */
class ViewsFormAlterTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests hook_form_BASE_FORM_ID_alter for a ViewsForm.
   */
  public function testViewsFormAlter() {
    $this->drupalLogin($this->createUser(['access media overview']));
    $this->drupalGet('admin/content/media');
    $count = $this->container->get('state')->get('hook_form_BASE_FORM_ID_alter_count');
    $this->assertEquals(1, $count, 'hook_form_BASE_FORM_ID_alter was invoked only once');
  }

}
