<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\XssTest.
 */

namespace Drupal\views_ui\Tests;

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
  public static $modules = array('node', 'user', 'views_ui', 'views_ui_test');

  public function testViewsUi() {
    $this->drupalGet('admin/structure/views');
    $this->assertEscaped('<script>alert("foo");</script>, <marquee>test</marquee>', 'The view tag is properly escaped.');

    $this->drupalGet('admin/structure/views/view/sa_contrib_2013_035');
    $this->assertEscaped('<marquee>test</marquee>', 'Field admin label is properly escaped.');

    $this->drupalGet('admin/structure/views/nojs/handler/sa_contrib_2013_035/page_1/header/area');
    $this->assertRaw('{{ title }} == &amp;lt;marquee&amp;gt;test&amp;lt;/marquee&amp;gt;', 'Token label is properly escaped.');
    $this->assertRaw('{{ title_1 }} == &amp;lt;script&amp;gt;alert(&amp;quot;XSS&amp;quot;)&amp;lt;/script&amp;gt;', 'Token label is properly escaped.');
  }

}
