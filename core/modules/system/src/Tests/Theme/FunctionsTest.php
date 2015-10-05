<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\FunctionsTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for common theme functions.
 *
 * @group Theme
 */
class FunctionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test');

  /**
   * Tests item-list.html.twig.
   */
  function testItemList() {
    // Verify that empty items produce no output.
    $variables = array();
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates no output.');

    // Verify that empty items with title produce no output.
    $variables = array();
    $variables['title'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback with title generates no output.');

    // Verify that empty items produce the empty string.
    $variables = array();
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list">No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates empty string.');

    // Verify that empty items produce the empty string with title.
    $variables = array();
    $variables['title'] = 'Some title';
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3>Some title</h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates empty string with title.');

    // Verify that title set to 0 is output.
    $variables = array();
    $variables['title'] = 0;
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3>0</h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback with title set to 0 generates a title.');

    // Verify that title set to a render array is output.
    $variables = array();
    $variables['title'] = array(
      '#markup' => '<span>Render array</span>',
    );
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3><span>Render array</span></h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback with title set to a render array generates a title.');

    // Verify that empty text is not displayed when there are list items.
    $variables = array();
    $variables['title'] = 'Some title';
    $variables['empty'] = 'No items found.';
    $variables['items'] = array('Un', 'Deux', 'Trois');
    $expected = '<div class="item-list"><h3>Some title</h3><ul><li>Un</li><li>Deux</li><li>Trois</li></ul></div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback does not print empty text when there are list items.');

    // Verify nested item lists.
    $variables = array();
    $variables['title'] = 'Some title';
    $variables['attributes'] = array(
      'id' => 'parentlist',
    );
    $variables['items'] = array(
      // A plain string value forms an own item.
      'a',
      // Items can be fully-fledged render arrays with their own attributes.
      array(
        '#wrapper_attributes' => array(
          'id' => 'item-id-b',
        ),
        '#markup' => 'b',
        'childlist' => array(
          '#theme' => 'item_list',
          '#attributes' => array('id' => 'blist'),
          '#list_type' => 'ol',
          '#items' => array(
            'ba',
            array(
              '#markup' => 'bb',
              '#wrapper_attributes' => array('class' => array('item-class-bb')),
            ),
          ),
        ),
      ),
      // However, items can also be child #items.
      array(
        '#markup' => 'c',
        'childlist' => array(
          '#attributes' => array('id' => 'clist'),
          'ca',
          array(
            '#markup' => 'cb',
            '#wrapper_attributes' => array('class' => array('item-class-cb')),
            'children' => array(
              'cba',
              'cbb',
            ),
          ),
          'cc',
        ),
      ),
      // Use #markup to be able to specify #wrapper_attributes.
      array(
        '#markup' => 'd',
        '#wrapper_attributes' => array('id' => 'item-id-d'),
      ),
      // An empty item with attributes.
      array(
        '#wrapper_attributes' => array('id' => 'item-id-e'),
      ),
      // Lastly, another plain string item.
      'f',
    );

    $inner_b = '<div class="item-list"><ol id="blist">';
    $inner_b .= '<li>ba</li>';
    $inner_b .= '<li class="item-class-bb">bb</li>';
    $inner_b .= '</ol></div>';

    $inner_cb = '<div class="item-list"><ul>';
    $inner_cb .= '<li>cba</li>';
    $inner_cb .= '<li>cbb</li>';
    $inner_cb .= '</ul></div>';

    $inner_c = '<div class="item-list"><ul id="clist">';
    $inner_c .= '<li>ca</li>';
    $inner_c .= '<li class="item-class-cb">cb' . $inner_cb . '</li>';
    $inner_c .= '<li>cc</li>';
    $inner_c .= '</ul></div>';

    $expected = '<div class="item-list">';
    $expected .= '<h3>Some title</h3>';
    $expected .= '<ul id="parentlist">';
    $expected .= '<li>a</li>';
    $expected .= '<li id="item-id-b">b' . $inner_b . '</li>';
    $expected .= '<li>c' . $inner_c . '</li>';
    $expected .= '<li id="item-id-d">d</li>';
    $expected .= '<li id="item-id-e"></li>';
    $expected .= '<li>f</li>';
    $expected .= '</ul></div>';

    $this->assertThemeOutput('item_list', $variables, $expected);
  }

  /**
   * Tests links.html.twig.
   */
  function testLinks() {
    // Turn off the query for the
    // \Drupal\Core\Utility\LinkGeneratorInterface::generate() method to compare
    // the active link correctly.
    $original_query = \Drupal::request()->query->all();
    \Drupal::request()->query->replace(array());
    // Verify that empty variables produce no output.
    $variables = array();
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback generates no output.');

    $variables = array();
    $variables['heading'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback with heading generates no output.');

    // Verify that a list of links is properly rendered.
    $variables = array();
    $variables['attributes'] = array('id' => 'somelinks');
    $variables['links'] = array(
      'a link' => array(
        'title' => 'A <link>',
        'url' => Url::fromUri('base:a/link'),
      ),
      'plain text' => array(
        'title' => 'Plain "text"',
      ),
      'html text' => array(
        'title' => SafeMarkup::format('<span class="unescaped">@text</span>', array('@text' => 'potentially unsafe text that <should> be escaped')),
      ),
      'front page' => array(
        'title' => 'Front page',
        'url' => Url::fromRoute('<front>'),
      ),
      'router-test' => array(
        'title' => 'Test route',
        'url' => Url::fromRoute('router_test.1'),
      ),
      'query-test' => array(
        'title' => 'Query test route',
        'url' => Url::fromRoute('router_test.1'),
        'query' => array(
          'key' => 'value',
        )
      ),
    );

    $expected_links = '';
    $expected_links .= '<ul id="somelinks">';
    $expected_links .= '<li class="a-link"><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li class="plain-text">' . Html::escape('Plain "text"') . '</li>';
    $expected_links .= '<li class="html-text"><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li class="front-page"><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li class="router-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = array('key' => 'value');
    $expected_links .= '<li class="query-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';

    // Verify that passing a string as heading works.
    $variables['heading'] = 'Links heading';
    $expected_heading = '<h2>Links heading</h2>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Restore the original request's query.
    \Drupal::request()->query->replace($original_query);

    // Verify that passing an array as heading works (core support).
    $variables['heading'] = array(
      'text' => 'Links heading',
      'level' => 'h3',
      'attributes' => array('class' => array('heading')),
    );
    $expected_heading = '<h3 class="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the heading works.
    $variables['heading'] = array('text' => 'Links heading', 'level' => 'h3', 'attributes' => array('id' => 'heading'));
    $expected_heading = '<h3 id="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the links work.
    $variables['links']['plain text']['attributes'] = array(
      'class' => array('a/class'),
    );
    $expected_links = '';
    $expected_links .= '<ul id="somelinks">';
    $expected_links .= '<li class="a-link"><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li class="plain-text"><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li class="html-text"><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li class="front-page"><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li class="router-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = array('key' => 'value');
    $expected_links .= '<li class="query-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify the data- attributes for setting the "active" class on links.
    \Drupal::currentUser()->setAccount(new UserSession(array('uid' => 1)));
    $variables['set_active_class'] = TRUE;
    $expected_links = '';
    $expected_links .= '<ul id="somelinks">';
    $expected_links .= '<li class="a-link"><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li class="plain-text"><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li class="html-text"><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li data-drupal-link-system-path="&lt;front&gt;" class="front-page"><a href="' . Url::fromRoute('<front>')->toString() . '" data-drupal-link-system-path="&lt;front&gt;">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li data-drupal-link-system-path="router_test/test1" class="router-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" data-drupal-link-system-path="router_test/test1">' . Html::escape('Test route') . '</a></li>';
    $query = array('key' => 'value');
    $encoded_query = Html::escape(Json::encode($query));
    $expected_links .= '<li data-drupal-link-query="'.$encoded_query.'" data-drupal-link-system-path="router_test/test1" class="query-test"><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '" data-drupal-link-query="'.$encoded_query.'" data-drupal-link-system-path="router_test/test1">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);
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
          'url' => Url::fromRoute('router_test.1'),
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
            'url' => Url::fromRoute('router_test.6'),
          ),
          // This should always be rendered.
          'first_child_link' => array(
            'title' => 'First child link',
            'url' => Url::fromRoute('router_test.7'),
          ),
        ),
      ),
      // This should always be rendered as part of the parent.
      'second_child' => array(
        '#theme' => 'links',
        '#links' => array(
          'second_child_link' => array(
            'title' => 'Second child link',
            'url' => Url::fromRoute('router_test.8'),
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
            'url' => Url::fromRoute('router_test.9'),
          ),
        ),
        '#access' => FALSE,
      ),
    );

    // Start with a fresh copy of the base array, and try rendering the entire
    // thing. We expect a single <ul> with appropriate links contained within
    // it.
    $render_array = $base_array;
    $html = \Drupal::service('renderer')->renderRoot($render_array);
    $dom = new \DOMDocument();
    $dom->loadHTML($html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, 'One "ul" tag found in the rendered HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 3, 'Three "li" tags found in the rendered HTML.');
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link original', 'First expected link found.');
    $this->assertEqual($list_elements->item(1)->nodeValue, 'First child link', 'Second expected link found.');
    $this->assertEqual($list_elements->item(2)->nodeValue, 'Second child link', 'Third expected link found.');
    $this->assertIdentical(strpos($html, 'Parent link copy'), FALSE, '"Parent link copy" link not found.');
    $this->assertIdentical(strpos($html, 'Third child link'), FALSE, '"Third child link" link not found.');

    // Now render 'first_child', followed by the rest of the links, and make
    // sure we get two separate <ul>'s with the appropriate links contained
    // within each.
    $render_array = $base_array;
    $child_html = \Drupal::service('renderer')->renderRoot($render_array['first_child']);
    $parent_html = \Drupal::service('renderer')->renderRoot($render_array);
    // First check the child HTML.
    $dom = new \DOMDocument();
    $dom->loadHTML($child_html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, 'One "ul" tag found in the rendered child HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 2, 'Two "li" tags found in the rendered child HTML.');
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link copy', 'First expected link found.');
    $this->assertEqual($list_elements->item(1)->nodeValue, 'First child link', 'Second expected link found.');
    // Then check the parent HTML.
    $dom = new \DOMDocument();
    $dom->loadHTML($parent_html);
    $this->assertEqual($dom->getElementsByTagName('ul')->length, 1, 'One "ul" tag found in the rendered parent HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEqual($list_elements->length, 2, 'Two "li" tags found in the rendered parent HTML.');
    $this->assertEqual($list_elements->item(0)->nodeValue, 'Parent link original', 'First expected link found.');
    $this->assertEqual($list_elements->item(1)->nodeValue, 'Second child link', 'Second expected link found.');
    $this->assertIdentical(strpos($parent_html, 'First child link'), FALSE, '"First child link" link not found.');
    $this->assertIdentical(strpos($parent_html, 'Third child link'), FALSE, '"Third child link" link not found.');
  }

  /**
   * Tests theme_image().
   */
  function testImage() {
    // Test that data URIs work with theme_image().
    $variables = array();
    $variables['uri'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
    $variables['alt'] = 'Data URI image of a red dot';
    $expected = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Data URI image of a red dot" />' . "\n";
    $this->assertThemeOutput('image', $variables, $expected);
  }

}
