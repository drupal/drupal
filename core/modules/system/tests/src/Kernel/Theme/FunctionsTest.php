<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for common theme functions.
 *
 * @group Theme
 */
class FunctionsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['router_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the Starterkit theme.
    $this->container->get('theme_installer')->install(['starterkit_theme']);
    $this->config('system.theme')->set('default', 'starterkit_theme')->save();
  }

  /**
   * Tests item-list.html.twig.
   */
  public function testItemList() {
    // Verify that empty items produce no output.
    $variables = [];
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates no output.');

    // Verify that empty items with title produce no output.
    $variables = [];
    $variables['title'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback with title generates no output.');

    // Verify that empty items produce the empty string.
    $variables = [];
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list">No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates empty string.');

    // Verify that empty items produce the empty string with title.
    $variables = [];
    $variables['title'] = 'Some title';
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3>Some title</h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, 'Empty %callback generates empty string with title.');

    // Verify that title set to 0 is output.
    $variables = [];
    $variables['title'] = 0;
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3>0</h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback with title set to 0 generates a title.');

    // Verify that title set to a render array is output.
    $variables = [];
    $variables['title'] = [
      '#markup' => '<span>Render array</span>',
    ];
    $variables['empty'] = 'No items found.';
    $expected = '<div class="item-list"><h3><span>Render array</span></h3>No items found.</div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback with title set to a render array generates a title.');

    // Verify that empty text is not displayed when there are list items.
    $variables = [];
    $variables['title'] = 'Some title';
    $variables['empty'] = 'No items found.';
    $variables['items'] = ['Un', 'Deux', 'Trois'];
    $expected = '<div class="item-list"><h3>Some title</h3><ul><li>Un</li><li>Deux</li><li>Trois</li></ul></div>';
    $this->assertThemeOutput('item_list', $variables, $expected, '%callback does not print empty text when there are list items.');

    // Verify nested item lists.
    $variables = [];
    $variables['title'] = 'Some title';
    $variables['attributes'] = [
      'id' => 'parent-list',
    ];
    $variables['items'] = [
      // A plain string value forms an own item.
      'a',
      // Items can be fully-fledged render arrays with their own attributes.
      [
        '#wrapper_attributes' => [
          'id' => 'item-id-b',
        ],
        '#markup' => 'b',
        'child_list' => [
          '#theme' => 'item_list',
          '#attributes' => ['id' => 'b_list'],
          '#list_type' => 'ol',
          '#items' => [
            'ba',
            [
              '#markup' => 'bb',
              '#wrapper_attributes' => ['class' => ['item-class-bb']],
            ],
          ],
        ],
      ],
      // However, items can also be child #items.
      [
        '#markup' => 'c',
        'child_list' => [
          '#attributes' => ['id' => 'c-list'],
          'ca',
          [
            '#markup' => 'cb',
            '#wrapper_attributes' => ['class' => ['item-class-cb']],
            'children' => [
              'cba',
              'cbb',
            ],
          ],
          'cc',
        ],
      ],
      // Use #markup to be able to specify #wrapper_attributes.
      [
        '#markup' => 'd',
        '#wrapper_attributes' => ['id' => 'item-id-d'],
      ],
      // An empty item with attributes.
      [
        '#wrapper_attributes' => ['id' => 'item-id-e'],
      ],
      // Lastly, another plain string item.
      'f',
    ];

    $inner_b = '<div class="item-list"><ol id="b_list">';
    $inner_b .= '<li>ba</li>';
    $inner_b .= '<li class="item-class-bb">bb</li>';
    $inner_b .= '</ol></div>';

    $inner_cb = '<div class="item-list"><ul>';
    $inner_cb .= '<li>cba</li>';
    $inner_cb .= '<li>cbb</li>';
    $inner_cb .= '</ul></div>';

    $inner_c = '<div class="item-list"><ul id="c-list">';
    $inner_c .= '<li>ca</li>';
    $inner_c .= '<li class="item-class-cb">cb' . $inner_cb . '</li>';
    $inner_c .= '<li>cc</li>';
    $inner_c .= '</ul></div>';

    $expected = '<div class="item-list">';
    $expected .= '<h3>Some title</h3>';
    $expected .= '<ul id="parent-list">';
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
  public function testLinks() {
    // Turn off the query for the
    // \Drupal\Core\Utility\LinkGeneratorInterface::generate() method to compare
    // the active link correctly.
    $original_query = \Drupal::request()->query->all();
    \Drupal::request()->query->replace([]);
    // Verify that empty variables produce no output.
    $variables = [];
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback generates no output.');

    $variables = [];
    $variables['heading'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback with heading generates no output.');

    // Verify that a list of links is properly rendered.
    $variables = [];
    $variables['attributes'] = ['id' => 'some_links'];
    $variables['links'] = [
      'a link' => [
        'title' => 'A <link>',
        'url' => Url::fromUri('base:a/link'),
      ],
      'plain text' => [
        'title' => 'Plain "text"',
      ],
      'html text' => [
        'title' => new FormattableMarkup('<span class="unescaped">@text</span>', ['@text' => 'potentially unsafe text that <should> be escaped']),
      ],
      'front page' => [
        'title' => 'Front page',
        'url' => Url::fromRoute('<front>'),
      ],
      'router-test' => [
        'title' => 'Test route',
        'url' => Url::fromRoute('router_test.1'),
      ],
      'query-test' => [
        'title' => 'Query test route',
        'url' => Url::fromRoute('router_test.1'),
        'query' => [
          'key' => 'value',
        ],
      ],
    ];

    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li>' . Html::escape('Plain "text"') . '</li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';

    // Verify that passing a string as heading works.
    $variables['heading'] = 'Links heading';
    $expected_heading = '<h2>Links heading</h2>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Restore the original request's query.
    \Drupal::request()->query->replace($original_query);

    // Verify that passing an array as heading works (core support).
    $variables['heading'] = [
      'text' => 'Links heading',
      'level' => 'h3',
      'attributes' => ['class' => ['heading']],
    ];
    $expected_heading = '<h3 class="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the heading works.
    $variables['heading'] = ['text' => 'Links heading', 'level' => 'h3', 'attributes' => ['id' => 'heading']];
    $expected_heading = '<h3 id="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the links work.
    $variables['links']['plain text']['attributes'] = [
      'class' => ['a/class'],
    ];
    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify the data- attributes for setting the "active" class on links.
    \Drupal::currentUser()->setAccount(new UserSession(['uid' => 1]));
    $variables['set_active_class'] = TRUE;
    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li data-drupal-link-system-path="&lt;front&gt;"><a href="' . Url::fromRoute('<front>')->toString() . '" data-drupal-link-system-path="&lt;front&gt;">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li data-drupal-link-system-path="router_test/test1"><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" data-drupal-link-system-path="router_test/test1">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $encoded_query = Html::escape(Json::encode($query));
    $expected_links .= '<li data-drupal-link-query="' . $encoded_query . '" data-drupal-link-system-path="router_test/test1"><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '" data-drupal-link-query="' . $encoded_query . '" data-drupal-link-system-path="router_test/test1">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);
  }

  /**
   * Tests links.html.twig using links with indexed keys.
   */
  public function testIndexedKeyedLinks() {
    // Turn off the query for the
    // \Drupal\Core\Utility\LinkGeneratorInterface::generate() method to compare
    // the active link correctly.
    $original_query = \Drupal::request()->query->all();
    \Drupal::request()->query->replace([]);
    // Verify that empty variables produce no output.
    $variables = [];
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback generates no output.');

    $variables = [];
    $variables['heading'] = 'Some title';
    $expected = '';
    $this->assertThemeOutput('links', $variables, $expected, 'Empty %callback with heading generates no output.');

    // Verify that a list of links is properly rendered.
    $variables = [];
    $variables['attributes'] = ['id' => 'some_links'];
    $variables['links'] = [
      [
        'title' => 'A <link>',
        'url' => Url::fromUri('base:a/link'),
      ],
      [
        'title' => 'Plain "text"',
      ],
      [
        'title' => new FormattableMarkup('<span class="unescaped">@text</span>', ['@text' => 'potentially unsafe text that <should> be escaped']),
      ],
      [
        'title' => 'Front page',
        'url' => Url::fromRoute('<front>'),
      ],
      [
        'title' => 'Test route',
        'url' => Url::fromRoute('router_test.1'),
      ],
      [
        'title' => 'Query test route',
        'url' => Url::fromRoute('router_test.1'),
        'query' => [
          'key' => 'value',
        ],
      ],
    ];

    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li>' . Html::escape('Plain "text"') . '</li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';

    // Verify that passing a string as heading works.
    $variables['heading'] = 'Links heading';
    $expected_heading = '<h2>Links heading</h2>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Restore the original request's query.
    \Drupal::request()->query->replace($original_query);

    // Verify that passing an array as heading works (core support).
    $variables['heading'] = [
      'text' => 'Links heading',
      'level' => 'h3',
      'attributes' => ['class' => ['heading']],
    ];
    $expected_heading = '<h3 class="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the heading works.
    $variables['heading'] = ['text' => 'Links heading', 'level' => 'h3', 'attributes' => ['id' => 'heading']];
    $expected_heading = '<h3 id="heading">Links heading</h3>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify that passing attributes for the links work.
    $variables['links'][1]['attributes'] = [
      'class' => ['a/class'],
    ];
    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li><a href="' . Url::fromRoute('<front>')->toString() . '">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $expected_links .= '<li><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);

    // Verify the data- attributes for setting the "active" class on links.
    \Drupal::currentUser()->setAccount(new UserSession(['uid' => 1]));
    $variables['set_active_class'] = TRUE;
    $expected_links = '';
    $expected_links .= '<ul id="some_links">';
    $expected_links .= '<li><a href="' . Url::fromUri('base:a/link')->toString() . '">' . Html::escape('A <link>') . '</a></li>';
    $expected_links .= '<li><span class="a/class">' . Html::escape('Plain "text"') . '</span></li>';
    $expected_links .= '<li><span class="unescaped">' . Html::escape('potentially unsafe text that <should> be escaped') . '</span></li>';
    $expected_links .= '<li data-drupal-link-system-path="&lt;front&gt;"><a href="' . Url::fromRoute('<front>')->toString() . '" data-drupal-link-system-path="&lt;front&gt;">' . Html::escape('Front page') . '</a></li>';
    $expected_links .= '<li data-drupal-link-system-path="router_test/test1"><a href="' . \Drupal::urlGenerator()->generate('router_test.1') . '" data-drupal-link-system-path="router_test/test1">' . Html::escape('Test route') . '</a></li>';
    $query = ['key' => 'value'];
    $encoded_query = Html::escape(Json::encode($query));
    $expected_links .= '<li data-drupal-link-query="' . $encoded_query . '" data-drupal-link-system-path="router_test/test1"><a href="' . \Drupal::urlGenerator()->generate('router_test.1', $query) . '" data-drupal-link-query="' . $encoded_query . '" data-drupal-link-system-path="router_test/test1">' . Html::escape('Query test route') . '</a></li>';
    $expected_links .= '</ul>';
    $expected = $expected_heading . $expected_links;
    $this->assertThemeOutput('links', $variables, $expected);
  }

  /**
   * Tests the use of Link::preRenderLinks() on a nested array of links.
   *
   * @see \Drupal\Core\Render\Element\Link::preRenderLinks()
   */
  public function testDrupalPreRenderLinks() {
    // Define the base array to be rendered, containing a variety of different
    // kinds of links.
    $base_array = [
      '#theme' => 'links',
      '#pre_render' => [[Link::class, 'preRenderLinks']],
      '#links' => [
        'parent_link' => [
          'title' => 'Parent link original',
          'url' => Url::fromRoute('router_test.1'),
        ],
      ],
      'first_child' => [
        '#theme' => 'links',
        '#links' => [
          // This should be rendered if 'first_child' is rendered separately,
          // but ignored if the parent is being rendered (since it duplicates
          // one of the parent's links).
          'parent_link' => [
            'title' => 'Parent link copy',
            'url' => Url::fromRoute('router_test.6'),
          ],
          // This should always be rendered.
          'first_child_link' => [
            'title' => 'First child link',
            'url' => Url::fromRoute('router_test.7'),
          ],
        ],
      ],
      // This should always be rendered as part of the parent.
      'second_child' => [
        '#theme' => 'links',
        '#links' => [
          'second_child_link' => [
            'title' => 'Second child link',
            'url' => Url::fromRoute('router_test.8'),
          ],
        ],
      ],
      // This should never be rendered, since the user does not have access to
      // it.
      'third_child' => [
        '#theme' => 'links',
        '#links' => [
          'third_child_link' => [
            'title' => 'Third child link',
            'url' => Url::fromRoute('router_test.9'),
          ],
        ],
        '#access' => FALSE,
      ],
    ];

    // Start with a fresh copy of the base array, and try rendering the entire
    // thing. We expect a single <ul> with appropriate links contained within
    // it.
    $render_array = $base_array;
    $html = (string) \Drupal::service('renderer')->renderRoot($render_array);
    $dom = Html::load($html);
    $this->assertEquals(1, $dom->getElementsByTagName('ul')->length, 'One "ul" tag found in the rendered HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEquals(3, $list_elements->length, 'Three "li" tags found in the rendered HTML.');
    $this->assertEquals('Parent link original', $list_elements->item(0)->nodeValue, 'First expected link found.');
    $this->assertEquals('First child link', $list_elements->item(1)->nodeValue, 'Second expected link found.');
    $this->assertEquals('Second child link', $list_elements->item(2)->nodeValue, 'Third expected link found.');
    $this->assertStringNotContainsString('Parent link copy', $html, '"Parent link copy" link not found.');
    $this->assertStringNotContainsString('Third child link', $html, '"Third child link" link not found.');

    // Now render 'first_child', followed by the rest of the links, and make
    // sure we get two separate <ul>'s with the appropriate links contained
    // within each.
    $render_array = $base_array;
    $child_html = (string) \Drupal::service('renderer')->renderRoot($render_array['first_child']);
    $parent_html = (string) \Drupal::service('renderer')->renderRoot($render_array);
    // First check the child HTML.
    $dom = Html::load($child_html);
    $this->assertEquals(1, $dom->getElementsByTagName('ul')->length, 'One "ul" tag found in the rendered child HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEquals(2, $list_elements->length, 'Two "li" tags found in the rendered child HTML.');
    $this->assertEquals('Parent link copy', $list_elements->item(0)->nodeValue, 'First expected link found.');
    $this->assertEquals('First child link', $list_elements->item(1)->nodeValue, 'Second expected link found.');
    // Then check the parent HTML.
    $dom = Html::load($parent_html);
    $this->assertEquals(1, $dom->getElementsByTagName('ul')->length, 'One "ul" tag found in the rendered parent HTML.');
    $list_elements = $dom->getElementsByTagName('li');
    $this->assertEquals(2, $list_elements->length, 'Two "li" tags found in the rendered parent HTML.');
    $this->assertEquals('Parent link original', $list_elements->item(0)->nodeValue, 'First expected link found.');
    $this->assertEquals('Second child link', $list_elements->item(1)->nodeValue, 'Second expected link found.');
    $this->assertStringNotContainsString('First child link', $parent_html, '"First child link" link not found.');
    $this->assertStringNotContainsString('Third child link', $parent_html, '"Third child link" link not found.');
  }

  /**
   * Tests theme_image().
   */
  public function testImage() {
    // Test that data URIs work with theme_image().
    $variables = [];
    $variables['uri'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
    $variables['alt'] = 'Data URI image of a red dot';
    $expected = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==" alt="Data URI image of a red dot" />' . "\n";
    $this->assertThemeOutput('image', $variables, $expected);
  }

}
