<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\AreaTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Component\Utility\Xss;
use Drupal\views\Views;

/**
 * Tests the plugin base of the area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\AreaPluginBase
 * @see \Drupal\views_test\Plugin\views\area\TestExample
 */
class AreaTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_example_area', 'test_example_area_access');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'views_ui');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  protected function viewsData() {
    $data = parent::viewsData();
    $data['views']['test_example'] = array(
      'title' => 'Test Example area',
      'help' => 'A area handler which just exists for tests.',
      'area' => array(
        'id' => 'test_example'
      )
    );

    return $data;
  }


  /**
   * Tests the generic UI of a area handler.
   */
  public function testUI() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $types = array('header', 'footer', 'empty');
    $labels = array();
    foreach ($types as $type) {
      $edit_path = 'admin/structure/views/nojs/handler/test_example_area/default/' . $type .'/test_example';

      // First setup an empty label.
      $this->drupalPostForm($edit_path, array(), t('Apply'));
      $this->assertText('Test Example area');

      // Then setup a no empty label.
      $labels[$type] = $this->randomMachineName();
      $this->drupalPostForm($edit_path, array('options[admin_label]' => $labels[$type]), t('Apply'));
      // Make sure that the new label appears on the site.
      $this->assertText($labels[$type]);

      // Test that the settings (empty/admin_label) are accessible.
      $this->drupalGet($edit_path);
      $this->assertField('options[admin_label]');
      if ($type !== 'empty') {
        $this->assertField('options[empty]');
      }
    }
  }

  /**
   * Tests the rendering of an area.
   */
  public function testRenderArea() {
    $view = Views::getView('test_example_area');
    $view->initHandlers();

    // Insert a random string with XSS injection in the test area plugin.
    // Ensure that the string is rendered for the header, footer, and empty
    // text with the markup properly escaped.
    $header_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';
    $footer_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';
    $empty_string = '<script type="text/javascript">alert("boo");</script><p>' . $this->randomMachineName() . '</p>';

    $view->header['test_example']->options['string'] = $header_string;
    $view->header['test_example']->options['empty'] = TRUE;

    $view->footer['test_example']->options['string'] = $footer_string;
    $view->footer['test_example']->options['empty'] = TRUE;

    $view->empty['test_example']->options['string'] = $empty_string;

    // Check whether the strings exist in the output and are sanitized.
    $output = $view->preview();
    $output = $this->container->get('renderer')->renderRoot($output);
    $this->assertTrue(strpos($output, Xss::filterAdmin($header_string)) !== FALSE, 'Views header exists in the output and is sanitized');
    $this->assertTrue(strpos($output, Xss::filterAdmin($footer_string)) !== FALSE, 'Views footer exists in the output and is sanitized');
    $this->assertTrue(strpos($output, Xss::filterAdmin($empty_string)) !== FALSE, 'Views empty exists in the output and is sanitized');
    $this->assertTrue(strpos($output, '<script') === FALSE, 'Script tags were escaped');
  }

  /**
   * Tests that the header and footer areas are not rendered if empty.
   */
  public function testRenderEmptyHeaderFooter() {
    $view = Views::getView('test_example_area');
    $view->initHandlers();

    // Set example empty text.
    $view->empty['test_example']->options['string'] = '<p>' . $this->randomMachineName() . '</p>';

    $xpath = '//div[contains(@class, :class)]';

    // Verify that the empty header and footer sections have not been rendered.
    $output = $view->preview();
    $html = $this->container->get('renderer')->renderRoot($output);
    $this->setRawContent($html);
    $this->assertEqual(0, count($this->xpath($xpath, [':class' => 'view-header'])));
    $this->assertEqual(0, count($this->xpath($xpath, [':class' => 'view-footer'])));

    // Set example header text.
    $view->header['test_example']->options['string'] = '<p>' . $this->randomMachineName() . '</p>';
    $view->header['test_example']->options['empty'] = TRUE;

    // Set example footer text.
    $view->footer['test_example']->options['string'] = '<p>' . $this->randomMachineName() . '</p>';
    $view->footer['test_example']->options['empty'] = TRUE;

    // Verify that the header and footer sections have been rendered.
    $output = $view->preview();
    $html = $this->container->get('renderer')->renderRoot($output);
    $this->setRawContent($html);
    $this->assertEqual(1, count($this->xpath($xpath, [':class' => 'view-header'])));
    $this->assertEqual(1, count($this->xpath($xpath, [':class' => 'view-footer'])));
  }

  /**
   * Tests the access for an area.
   */
  public function testAreaAccess() {
    // Test with access denied for the area handler.
    $view = Views::getView('test_example_area_access');
    $view->initDisplay();
    $view->initHandlers();
    $handlers = $view->display_handler->getHandlers('empty');
    $this->assertEqual(0, count($handlers));

    $output = $view->preview();
    $output = \Drupal::service('renderer')->renderRoot($output);
    // The area output should not be present since access was denied.
    $this->assertFalse(strpos($output, 'a custom string') !== FALSE);
    $view->destroy();

    // Test with access granted for the area handler.
    $view = Views::getView('test_example_area_access');
    $view->initDisplay();
    $view->display_handler->overrideOption('empty', [
      'test_example' => [
        'field' => 'test_example',
        'id' => 'test_example',
        'table' => 'views',
        'plugin_id' => 'test_example',
        'string' => 'a custom string',
        'custom_access' => TRUE,
      ],
    ]);
    $view->initHandlers();
    $handlers = $view->display_handler->getHandlers('empty');

    $output = $view->preview();
    $output = \Drupal::service('renderer')->renderRoot($output);
    $this->assertTrue(strpos($output, 'a custom string') !== FALSE);
    $this->assertEqual(1, count($handlers));
  }

  /**
   * Tests global tokens.
   */
  public function testRenderAreaToken() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    $view = Views::getView('test_example_area');
    $view->initHandlers();

    $this->drupalGet('admin/structure/views/nojs/handler/test_example_area/default/empty/test_example');

    // Test that the list is token present.
    $element = $this->xpath('//ul[@class="global-tokens"]');
    $this->assertTrue($element, 'Token list found on the options form.');

    $empty_handler = &$view->empty['test_example'];

    // Test the list of available tokens.
    $available = $empty_handler->getAvailableGlobalTokens();
    foreach (array('site', 'view') as $type) {
      $this->assertTrue(!empty($available[$type]) && is_array($available[$type]));
      // Test that each item exists in the list.
      foreach ($available[$type] as $token => $info) {
        $this->assertText("[$type:$token]");
      }
    }

    // Test the rendered output of a token.
    $empty_handler->options['string'] = '[site:name]';

    // Test we have the site:name token in the output.
    $output = $view->preview();
    $output = $this->container->get('renderer')->renderRoot($output);
    $expected = \Drupal::token()->replace('[site:name]');
    $this->assertTrue(strpos($output, $expected) !== FALSE);
  }

  /**
   * Tests overriding the view title using the area title handler.
   */
  public function testTitleArea() {
    $view = Views::getView('frontpage');
    $view->initDisplay('page_1');

    // Add the title area handler to the empty area.
    $view->displayHandlers->get('page_1')->overrideOption('empty', array(
      'title' => array(
        'id' => 'title',
        'table' => 'views',
        'field' => 'title',
        'admin_label' => '',
        'empty' => '0',
        'title' => 'Overridden title',
        'plugin_id' => 'title',
      ),
    ));

    $view->storage->enable()->save();

    $this->drupalGet('node');
    $this->assertText('Overridden title', 'Overridden title found.');
  }

}
