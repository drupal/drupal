<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;
use Drupal\views_test_data\Plugin\views\display\DisplayTest as DisplayTestPlugin;

/**
 * Tests the basic display plugin.
 *
 * @group views
 */
class DisplayTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_groups', 'test_get_attach_displays', 'test_view', 'test_display_more', 'test_display_invalid', 'test_display_empty', 'test_exposed_relationship_admin_ui', 'test_simple_argument'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui', 'node', 'block'];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // The availability of Views display plugins is validated by the config
    // system, but one of our test cases saves a view with an invalid display
    // plugin ID, to see how Views handles that. Therefore, allow that one view
    // to be saved with an invalid display plugin without angering the config
    // schema checker.
    // @see ::testInvalidDisplayPlugins()
    'views.view.test_display_invalid',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->drupalLogin($this->drupalCreateUser(['administer views']));

    // Create 10 nodes.
    for ($i = 0; $i <= 10; $i++) {
      $this->drupalCreateNode(['promote' => TRUE]);
    }
  }

  /**
   * Tests the display test plugin.
   *
   * @see \Drupal\views_test_data\Plugin\views\display\DisplayTest
   */
  public function testDisplayPlugin(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_view');

    // Add a new 'display_test' display and test it's there.
    $view->storage->addDisplay('display_test');
    $displays = $view->storage->get('display');

    $this->assertTrue(isset($displays['display_test_1']), 'Added display has been assigned to "display_test_1"');

    // Check the display options are like expected.
    $options = [
      'display_options' => [],
      'display_plugin' => 'display_test',
      'id' => 'display_test_1',
      'display_title' => 'Display test',
      'position' => 1,
    ];
    $this->assertEquals($options, $displays['display_test_1']);

    // Add another one to ensure that position is counted up.
    $view->storage->addDisplay('display_test');
    $displays = $view->storage->get('display');
    $options = [
      'display_options' => [],
      'display_plugin' => 'display_test',
      'id' => 'display_test_2',
      'display_title' => 'Display test 2',
      'position' => 2,
    ];
    $this->assertEquals($options, $displays['display_test_2']);

    // Move the second display before the first one in order to test custom
    // sorting.
    $displays['display_test_1']['position'] = 2;
    $displays['display_test_2']['position'] = 1;
    $view->storage->set('display', $displays);
    $view->save();

    $view->setDisplay('display_test_1');

    $this->assertInstanceOf(DisplayTestPlugin::class, $view->display_handler);

    // Check the test option.
    $this->assertSame('', $view->display_handler->getOption('test_option'));

    $style = $view->display_handler->getOption('style');
    $style['type'] = 'test_style';
    $view->display_handler->setOption('style', $style);
    $view->initDisplay();
    $view->initStyle();
    $view->style_plugin->setUsesRowPlugin(FALSE);

    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);

    $this->assertStringContainsString('<h1></h1>', $output, 'An empty value for test_option found in output.');

    // Change this option and check the title of out output.
    $view->display_handler->overrideOption('test_option', 'Test option title');
    $view->save();

    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);

    // Test we have our custom <h1> tag in the output of the view.
    $this->assertStringContainsString('<h1>Test option title</h1>', $output, 'The test_option value found in display output title.');

    // Test that the display category/summary is in the UI.
    $this->drupalGet('admin/structure/views/view/test_view/edit/display_test_1');
    $this->assertSession()->pageTextContains('Display test settings');
    // Ensure that the order is as expected.
    $result = $this->xpath('//ul[@id="views-display-menu-tabs"]/li/a/child::text()');
    $this->assertEquals('Display test 2', $result[0]->getText());
    $this->assertEquals('Display test', $result[1]->getText());

    $this->clickLink('Test option title');

    $test_option = $this->randomString();
    $this->submitForm(['test_option' => $test_option], 'Apply');

    // Check the new value has been saved by checking the UI summary text.
    $this->drupalGet('admin/structure/views/view/test_view/edit/display_test_1');
    $this->assertSession()->linkExists($test_option);

    // Test the enable/disable status of a display.
    $view->display_handler->setOption('enabled', FALSE);
    $this->assertFalse($view->display_handler->isEnabled(), 'Make sure that isEnabled returns FALSE on a disabled display.');
    $view->display_handler->setOption('enabled', TRUE);
    $this->assertTrue($view->display_handler->isEnabled(), 'Make sure that isEnabled returns TRUE on a disabled display.');
  }

  /**
   * Tests the overriding of filter_groups.
   */
  public function testFilterGroupsOverriding(): void {
    $view = Views::getView('test_filter_groups');
    $view->initDisplay();

    // Mark is as overridden, yes FALSE, means overridden.
    $view->displayHandlers->get('page')->setOverride('filter_groups', FALSE);
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filter_groups'), "Make sure that 'filter_groups' is marked as overridden.");
    $this->assertFalse($view->displayHandlers->get('page')->isDefaulted('filters'), "Make sure that 'filters'' is marked as overridden.");
  }

  /**
   * Tests the getAttachedDisplays method.
   */
  public function testGetAttachedDisplays(): void {
    $view = Views::getView('test_get_attach_displays');

    // Both the feed_1 and the feed_2 display are attached to the page display.
    $view->setDisplay('page_1');
    $this->assertEquals(['feed_1', 'feed_2'], $view->display_handler->getAttachedDisplays());

    $view->setDisplay('feed_1');
    $this->assertEquals([], $view->display_handler->getAttachedDisplays());
  }

  /**
   * Tests the readmore validation.
   */
  public function testReadMoreNoDisplay(): void {
    $view = Views::getView('test_display_more');
    // Confirm that the view validates when there is a page display.
    $errors = $view->validate();
    $this->assertEmpty($errors, 'More link validation has no errors.');

    // Confirm that the view does not validate when the page display is disabled.
    $view->setDisplay('page_1');
    $view->display_handler->setOption('enabled', FALSE);
    $view->setDisplay('default');
    $errors = $view->validate();
    $this->assertNotEmpty($errors, 'More link validation has some errors.');
    $this->assertEquals('Display "Default" uses a "more" link but there are no displays it can link to. You need to specify a custom URL.', $errors['default'][0], 'More link validation has the right error.');

    // Confirm that the view does not validate when the page display does not exist.
    $view = Views::getView('test_view');
    $view->setDisplay('default');
    $view->display_handler->setOption('use_more', 1);
    $errors = $view->validate();
    $this->assertNotEmpty($errors, 'More link validation has some errors.');
    $this->assertEquals('Display "Default" uses a "more" link but there are no displays it can link to. You need to specify a custom URL.', $errors['default'][0], 'More link validation has the right error.');
  }

  /**
   * Tests the readmore with custom URL.
   */
  public function testReadMoreCustomURL(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $view = Views::getView('test_display_more');
    $view->setDisplay('default');
    $view->display_handler->setOption('use_more', 1);
    $view->display_handler->setOption('use_more_always', 1);
    $view->display_handler->setOption('link_display', 'custom_url');

    // Test more link without leading slash.
    $view->display_handler->setOption('link_url', 'node');
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node', $output, 'The read more link with href "/node" was found.');

    // Test more link with leading slash.
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', '/node');
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node', $output, 'The read more link with href "/node" was found.');

    // Test more link with absolute URL.
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', 'http://example.com');
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('http://example.com', $output, 'The read more link with href "http://example.com" was found.');

    // Test more link with query parameters in the URL.
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', 'node?page=1&foo=bar');
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node?page=1&amp;foo=bar', $output, 'The read more link with href "/node?page=1&foo=bar" was found.');

    // Test more link with fragment in the URL.
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', 'node#target');
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node#target', $output, 'The read more link with href "/node#target" was found.');

    // Test more link with arguments.
    $view = Views::getView('test_simple_argument');
    $view->setDisplay('default');
    $view->display_handler->setOption('use_more', 1);
    $view->display_handler->setOption('use_more_always', 1);
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', 'node?date={{ raw_arguments.age }}&foo=bar');
    $view->setArguments([22]);
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node?date=22&amp;foo=bar', $output, 'The read more link with href "/node?date=22&foo=bar" was found.');

    // Test more link with 1 dimension array query parameters with arguments.
    $view = Views::getView('test_simple_argument');
    $view->setDisplay('default');
    $view->display_handler->setOption('use_more', 1);
    $view->display_handler->setOption('use_more_always', 1);
    $view->display_handler->setOption('link_display', 'custom_url');
    $view->display_handler->setOption('link_url', '/node?f[0]=foo:bar&f[1]=foo:{{ raw_arguments.age }}');
    $view->setArguments([22]);
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node?f%5B0%5D=foo%3Abar&amp;f%5B1%5D=foo%3A22', $output, 'The read more link with href "/node?f[0]=foo:bar&f[1]=foo:22" was found.');

    // Test more link with arguments in path.
    $view->display_handler->setOption('link_url', 'node/{{ raw_arguments.age }}?date={{ raw_arguments.age }}&foo=bar');
    $view->setArguments([22]);
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node/22?date=22&amp;foo=bar', $output, 'The read more link with href "/node/22?date=22&foo=bar" was found.');

    // Test more link with array arguments in path.
    $view->display_handler->setOption('link_url', 'node/{{ raw_arguments.age }}?date[{{ raw_arguments.age }}]={{ raw_arguments.age }}&foo=bar');
    $view->setArguments([22]);
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node/22?date%5B22%5D=22&amp;foo=bar', $output, 'The read more link with href "/node/22?date[22]=22&foo=bar" was found.');

    // Test more link with arguments in fragment.
    $view->display_handler->setOption('link_url', 'node?date={{ raw_arguments.age }}&foo=bar#{{ raw_arguments.age }}');
    $view->setArguments([22]);
    $this->executeView($view);
    $output = $view->preview();
    $output = (string) $renderer->renderRoot($output);
    $this->assertStringContainsString('/node?date=22&amp;foo=bar#22', $output, 'The read more link with href "/node?date=22&foo=bar#22" was found.');
  }

  /**
   * Tests invalid display plugins.
   */
  public function testInvalidDisplayPlugins(): void {
    $this->drupalGet('test_display_invalid');
    $this->assertSession()->statusCodeEquals(200);

    // Change the page plugin id to an invalid one. Bypass the entity system
    // so no menu rebuild was executed (so the path is still available).
    $config = $this->config('views.view.test_display_invalid');
    $config->set('display.page_1.display_plugin', 'invalid');
    $config->save();

    $this->drupalGet('test_display_invalid');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The "invalid" plugin does not exist.');

    // Rebuild the router, and ensure that the path is not accessible anymore.
    views_invalidate_cache();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $this->drupalGet('test_display_invalid');
    $this->assertSession()->statusCodeEquals(404);

    // Change the display plugin ID back to the correct ID.
    $config = $this->config('views.view.test_display_invalid');
    $config->set('display.page_1.display_plugin', 'page');
    $config->save();

    // Place the block display.
    $block = $this->drupalPlaceBlock('views_block:test_display_invalid-block_1', ['label' => 'Invalid display']);

    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementsCount('xpath', "//div[@id = 'block-{$block->id()}']", 1);

    // Change the block plugin ID to an invalid one.
    $config = $this->config('views.view.test_display_invalid');
    $config->set('display.block_1.display_plugin', 'invalid');
    $config->save();

    // Test the page is still displayed, the block not present, and has the
    // plugin warning message.
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The "invalid" plugin does not exist.');
    $this->assertSession()->elementNotExists('xpath', "//div[@id = 'block-{$block->id()}']");
  }

  /**
   * Tests display validation when a required relationship is missing.
   */
  public function testMissingRelationship(): void {
    $view = Views::getView('test_exposed_relationship_admin_ui');

    // Remove the relationship that is not used by other handlers.
    $view->removeHandler('default', 'relationship', 'uid_1');
    $errors = $view->validate();
    // Check that no error message is shown.
    $this->assertArrayNotHasKey('default', $errors, 'No errors found when removing unused relationship.');

    // Unset cached relationships (see DisplayPluginBase::getHandlers())
    unset($view->display_handler->handlers['relationship']);

    // Remove the relationship used by other handlers.
    $view->removeHandler('default', 'relationship', 'uid');
    // Validate display
    $errors = $view->validate();
    // Check that the error messages are shown.
    $this->assertCount(2, $errors['default'], 'Error messages found for required relationship');
    $this->assertEquals(new FormattableMarkup('The %relationship_name relationship used in %handler_type %handler is not present in the %display_name display.', ['%relationship_name' => 'uid', '%handler_type' => 'field', '%handler' => 'User: Last login', '%display_name' => 'Default']), $errors['default'][0]);
    $this->assertEquals(new FormattableMarkup('The %relationship_name relationship used in %handler_type %handler is not present in the %display_name display.', ['%relationship_name' => 'uid', '%handler_type' => 'field', '%handler' => 'User: Created', '%display_name' => 'Default']), $errors['default'][1]);
  }

  /**
   * Tests the outputIsEmpty method on the display.
   */
  public function testOutputIsEmpty(): void {
    $view = Views::getView('test_display_empty');
    $this->executeView($view);
    $this->assertNotEmpty($view->result);
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty.');
    $view->destroy();

    // Add a filter, so the view result is empty.
    $view->setDisplay('default');
    $item = [
      'table' => 'views_test_data',
      'field' => 'id',
      'id' => 'id',
      'value' => ['value' => 7297],
    ];
    $view->setHandler('default', 'filter', 'id', $item);
    $this->executeView($view);
    $this->assertEmpty($view->result, 'Ensure the result of the view is empty.');
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty, because the empty text still appears.');
    $view->destroy();

    // Remove the empty area, but mark the header area to still appear.
    $view->removeHandler('default', 'empty', 'area');
    $item = $view->getHandler('default', 'header', 'area');
    $item['empty'] = TRUE;
    $view->setHandler('default', 'header', 'area', $item);
    $this->executeView($view);
    $this->assertEmpty($view->result, 'Ensure the result of the view is empty.');
    $this->assertFalse($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as not empty, because the header text still appears.');
    $view->destroy();

    // Hide the header on empty results.
    $item = $view->getHandler('default', 'header', 'area');
    $item['empty'] = FALSE;
    $view->setHandler('default', 'header', 'area', $item);
    $this->executeView($view);
    $this->assertEmpty($view->result, 'Ensure the result of the view is empty.');
    $this->assertTrue($view->display_handler->outputIsEmpty(), 'Ensure the view output is marked as empty.');
  }

  /**
   * Tests translation rendering settings based on entity translatability.
   */
  public function testTranslationSetting(): void {
    \Drupal::service('module_installer')->install(['file']);

    // By default there should be no language settings.
    $this->checkTranslationSetting();
    \Drupal::service('module_installer')->install(['language']);

    // Enabling the language module should not make a difference.
    $this->checkTranslationSetting();

    // Making the site multilingual should let translatable entity types support
    // translation rendering.
    ConfigurableLanguage::createFromLangcode('it')->save();
    $this->checkTranslationSetting(TRUE);
  }

  /**
   * Asserts a node and a file based view for the translation setting.
   *
   * The file based view should never expose that setting. The node based view
   * should if the site is multilingual.
   *
   * @param bool $expected_node_translatability
   *   Whether the node based view should be expected to support translation
   *   settings.
   */
  protected function checkTranslationSetting($expected_node_translatability = FALSE): void {
    $not_supported_text = 'The view is not based on a translatable entity type or the site is not multilingual.';
    $supported_text = 'All content that supports translations will be displayed in the selected language.';

    $this->drupalGet('admin/structure/views/nojs/display/content/page_1/rendering_language');
    if ($expected_node_translatability) {
      $this->assertSession()->pageTextNotContains($not_supported_text);
      $this->assertSession()->pageTextContains($supported_text);
    }
    else {
      $this->assertSession()->pageTextContains($not_supported_text);
      $this->assertSession()->pageTextNotContains($supported_text);
    }

    $this->drupalGet('admin/structure/views/nojs/display/files/page_1/rendering_language');
    $this->assertSession()->pageTextContains($not_supported_text);
    $this->assertSession()->pageTextNotContains($supported_text);
  }

}
