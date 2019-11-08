<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the Xss vulnerability.
 *
 * @group views_ui
 */
class XssTest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'user', 'views_ui', 'views_ui_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testViewsUi() {
    $this->drupalGet('admin/structure/views/view/sa_contrib_2013_035');
    $this->assertEscaped('<marquee>test</marquee>', 'Field admin label is properly escaped.');

    $this->drupalGet('admin/structure/views/nojs/handler/sa_contrib_2013_035/page_1/header/area');
    $this->assertEscaped('{{ title }} == <marquee>test</marquee>', 'Token label is properly escaped.');
    $this->assertEscaped('{{ title_1 }} == <script>alert("XSS")</script>', 'Token label is properly escaped.');
  }

  /**
   * Checks the admin UI for double escaping.
   */
  public function testNoDoubleEscaping() {
    $this->drupalGet('admin/structure/views');
    $this->assertNoEscaped('&lt;');

    $this->drupalGet('admin/structure/views/view/sa_contrib_2013_035');
    $this->assertNoEscaped('&lt;');

    $this->drupalGet('admin/structure/views/nojs/handler/sa_contrib_2013_035/page_1/header/area');
    $this->assertNoEscaped('&lt;');
  }

}
