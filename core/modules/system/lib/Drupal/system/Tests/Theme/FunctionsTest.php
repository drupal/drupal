<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\FunctionsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use DOMDocument;

/**
 * Tests for common theme functions.
 */
class FunctionsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme functions',
      'description' => 'Tests common theme functions.',
      'group' => 'Theme',
    );
  }

  /**
   * Tests theme_item_list().
   */
  function testItemList() {
    // Verify that empty variables produce no output.
    $variables = array();
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates no output.');

    $variables = array();
    $variables['title'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback with title generates no output.');

    // Verify nested item lists.
    $variables = array();
    $variables['title'] = 'Some title';
    $variables['attributes'] = array(
      'id' => 'parentlist',
    );
    $variables['items'] = array(
      'a',
      array(
        'data' => 'b',
        'children' => array(
          'c',
          // Nested children may use additional attributes.
          array(
            'data' => 'd',
            'class' => array('dee'),
          ),
          // Any string key is treated as child list attribute.
          'id' => 'childlist',
        ),
        // Any other keys are treated as item attributes.
        'id' => 'bee',
      ),
      array(
        'data' => 'e',
        'id' => 'E',
      ),
    );
    $inner = '<div class="item-list"><ul id="childlist">';
    $inner .= '<li class="odd first">c</li>';
    $inner .= '<li class="dee even last">d</li>';
    $inner .= '</ul></div>';

    $expected = '<div class="item-list">';
    $expected .= '<h3>Some title</h3>';
    $expected .= '<ul id="parentlist">';
    $expected .= '<li class="odd first">a</li>';
    $expected .= '<li id="bee" class="even">b' . $inner . '</li>';
    $expected .= '<li id="E" class="odd last">e</li>';
    $expected .= '</ul></div>';

    $this->assertThemeOutput('item_list', $variables, $expected);
  }

  /**
   * Tests theme_links().
   */
  function testLinks() {
    // Verify that empty variables produce no output.
    $variables = array();
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback generates no output.');

    $variables = array();
    $variables['heading'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback with heading generates no output.');

    // Set the current path to the front page path.
    // Required to verify the "active" class in expected links below, and
    // because the current path is different when running tests manually via
    // simpletest.module ('batch') and via the testing framework ('').
    _current_path(variable_get('site_frontpage', 'user'));

    // Verify that a list of links is properly rendered.
    $variables = array();
    $variables['attributes'] = array('id' => 'somelinks');
    $variables['links'] = array(
      'a link' => array(
        'title' => 'A <link>',
        'href' => 'a/link',
      ),
      'plain text' => array(
        'title' => 'Plain "text"',
      ),
      'front page' => array(
        'title' => 'Front page',
        'href' => '<front>',
      ),
    );

    $expected_links = '';
    $expected_links .= '<ul id="somelinks">';
    $expected_links .= '<li class="a-link odd first"><a href="' . url('a/link') . '">' . check_plain('A <link>') . '</a></li>';
    $expected_links .= '<li class="plain-text even"><span>' . check_plain('Plain "text"') . '</span></li>';
    $expected_links .= '<li class="front-page odd last active"><a href="' . url('<front>') . '" class="active">' . check_plain('Front page') . '</a></li>';
    $expected_links .= '</ul>';

    // Verify that passing a string as heading works.
    $variables['heading'] = 'Links heading';
    $expected_heading = '<h2>Links heading</h2>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing an array as heading works (core support).
    $variables['heading'] = array('text' => 'Links heading', 'level' => 'h3', 'class' => 'heading');
    $expected_heading = '<h3 class="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the heading works.
    $variables['heading'] = array('text' => 'Links heading', 'level' => 'h3', 'attributes' => array('id' => 'heading'));
    $expected_heading = '<h3 id="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);
  }

  /**
   * Asserts themed output.
   *
   * @param $callback
   *   The name of the theme function to invoke; e.g. 'links' for theme_links().
   * @param $variables
   *   An array of variables to pass to the theme function.
   * @param $expected
   *   The expected themed output string.
   * @param $message
   *   (optional) An assertion message.
   */
  protected function assertThemeOutput($callback, array $variables = array(), $expected, $message = '') {
    $output = theme($callback, $variables);
    $this->verbose('Variables:' . '<pre>' .  check_plain(var_export($variables, TRUE)) . '</pre>'
      . '<hr />' . 'Result:' . '<pre>' .  check_plain(var_export($output, TRUE)) . '</pre>'
      . '<hr />' . 'Expected:' . '<pre>' .  check_plain(var_export($expected, TRUE)) . '</pre>'
      . '<hr />' . $output
    );
    if (!$message) {
      $message = '%callback rendered correctly.';
    }
    $message = t($message, array('%callback' => 'theme_' . $callback . '()'));
    $this->assertIdentical($output, $expected, $message);
  }

  /**
   * Test the use of drupal_pre_render_links() on a nested array of links.
   */
  function testDrupalPreRenderLinks() {
    // Define the base array to be rendered, containing a variety of different
    // kinds of links.
    $base_array = array(
      '#theme' => 'links',
      '#pre_render' => array('drupal_pre_render_links'),
      '#links' => array(
        'parent_link' => array(
          'title' => 'Parent link original',
          'href' => 'parent-link-original',
        ),
      ),
      'first_child' => array(
        '#theme' => 'links',
        '#links' => array(
          // This should be rendered if 'first_child' is rendered separately,
          // but ignored if the parent is being rendered (since it duplicates
          // one of the parent's links).
          'parent_link' => array(
            'title' => 'Parent link copy',
            'href' => 'parent-link-copy',
          ),
          // This should always be rendered.
          'first_child_link' => array(
            'title' => 'First child link',
            'href' => 'first-child-link',
          ),
        ),
      ),
      // This should always be rendered as part of the parent.
      'second_child' => array(
        '#theme' => 'links',
        '#links' => array(
          'second_child_link' => array(
            'title' => 'Second child link',
            'href' => 'second-child-link',
          ),
        ),
      ),
      // This should never be rendered, since the user does not have access to
      // it.
      'third_child' => array(
        '#theme' => 'links',
        '#links' => array(
          'third_child_link' => array(
            'title' => 'Third child link',
            'href' => 'third-child-link',
          ),
        ),
        '#access' => FALSE,
      ),
    );

    // Start with a fresh copy of the base array, and try rendering the entire
    // thing. We expect a single <ul> with appropriate links contained within
    // it.
    $render_array = $base_array;
    $html = drupal_render($render_array);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, t('One "ul" tag found in the rendered HTML.'));
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 3, t('Three "li" tags found in the rendered HTML.'));
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link original', t('First expected link found.'));
    $this->assertEqual($list_elements->item(1)->nodeValue, 'First child link', t('Second expected link found.'));
    $this->assertEqual($list_elements->item(2)->nodeValue, 'Second child link', t('Third expected link found.'));
    $this->assertIdentical(strpos($html, 'Parent link copy'), FALSE, t('"Parent link copy" link not found.'));
    $this->assertIdentical(strpos($html, 'Third child link'), FALSE, t('"Third child link" link not found.'));

    // Now render 'first_child', followed by the rest of the links, and make
    // sure we get two separate <ul>'s with the appropriate links contained
    // within each.
    $render_array = $base_array;
    $child_html = drupal_render($render_array['first_child']);
    $parent_html = drupal_render($render_array);
    // First check the child HTML.
    $dom = new DOMDocument();
    $dom->loadHTML($child_html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, t('One "ul" tag found in the rendered child HTML.'));
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 2, t('Two "li" tags found in the rendered child HTML.'));
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link copy', t('First expected link found.'));
    $this->assertEqual($list_elements->item(1)->nodeValue, 'First child link', t('Second expected link found.'));
    // Then check the parent HTML.
    $dom = new DOMDocument();
    $dom->loadHTML($parent_html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, t('One "ul" tag found in the rendered parent HTML.'));
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 2, t('Two "li" tags found in the rendered parent HTML.'));
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link original', t('First expected link found.'));
    $this->assertEqual($list_elements->item(1)->nodeValue, 'Second child link', t('Second expected link found.'));
    $this->assertIdentical(strpos($parent_html, 'First child link'), FALSE, t('"First child link" link not found.'));
    $this->assertIdentical(strpos($parent_html, 'Third child link'), FALSE, t('"Third child link" link not found.'));
  }
}
