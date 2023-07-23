<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the core views_handler_area_display_link handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\DisplayLink
 */
class AreaDisplayLinkTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'filter'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installConfig(['system', 'filter']);
    $this->installEntitySchema('user');

    $view = Views::getView('test_view');

    // Add two page displays and a block display.
    $page_1 = $view->newDisplay('page', 'Page 1', 'page_1');
    $page_1->setOption('path', 'page_1');
    $page_2 = $view->newDisplay('page', 'Page 2', 'page_2');
    $page_2->setOption('path', 'page_2');
    $view->newDisplay('block', 'Block 1', 'block_1');

    // Add default filter criteria, sort criteria, pager settings and contextual
    // filters.
    $default = $view->displayHandlers->get('default');
    $default->setOption('filters', [
      'status' => [
        'id' => 'status',
        'table' => 'views_test_data',
        'field' => 'status',
        'relationship' => 'none',
        'operator' => '=',
        'value' => 1,
      ],
    ]);
    $default->setOption('sorts', [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'order' => 'ASC',
      ],
    ]);
    $default->setOption('pager', [
      'type' => 'mini',
      'options' => ['items_per_page' => 10],
    ]);
    $default->setOption('arguments', [
      'uid' => [
        'id' => 'uid',
        'table' => 'views_test_data',
        'field' => 'uid',
        'relationship' => 'none',
      ],
    ]);

    // Add display links to both page displays.
    $display_links = [
      'display_link_1' => [
        'id' => 'display_link_1',
        'table' => 'views',
        'field' => 'display_link',
        'display_id' => 'page_1',
        'label' => 'Page 1',
        'plugin_id' => 'display_link',
      ],
      'display_link_2' => [
        'id' => 'display_link_2',
        'table' => 'views',
        'field' => 'display_link',
        'display_id' => 'page_2',
        'label' => 'Page 2',
        'plugin_id' => 'display_link',
      ],
    ];
    $default->setOption('header', $display_links);
    $view->save();

    // Ensure that the theme system does not log any errors about missing theme
    // hooks when rendering the link.
    $logger = $this->prophesize(LoggerInterface::class);
    $logger->log(
      RfcLogLevel::WARNING,
      'Theme hook %hook not found.',
      Argument::withEntry('%hook', 'link')
    )->shouldNotBeCalled();

    $this->container->get('logger.factory')
      ->get('theme')
      ->addLogger($logger->reveal());
  }

  /**
   * Tests the views area display_link handler.
   */
  public function testAreaDisplayLink() {
    $view = Views::getView('test_view');

    // Assert only path-based displays are available in the display link
    // settings form.
    $view->setDisplay('page_1');
    $this->assertFormOptions($view, 'display_link_1');
    $this->assertFormOptions($view, 'display_link_2');
    $view->setDisplay('page_2');
    $this->assertFormOptions($view, 'display_link_1');
    $this->assertFormOptions($view, 'display_link_2');
    $view->setDisplay('block_1');
    $this->assertFormOptions($view, 'display_link_1');
    $this->assertFormOptions($view, 'display_link_2');

    // Assert the links are rendered correctly for all displays.
    $this->assertRenderedDisplayLinks($view, 'page_1');
    $this->assertRenderedDisplayLinks($view, 'page_2');
    $this->assertRenderedDisplayLinks($view, 'block_1');

    // Assert some special request parameters are filtered from the display
    // links.
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('page_1', 'GET', [
      'name' => 'John',
      'sort_by' => 'created',
      'sort_order' => 'ASC',
      'page' => 1,
      'keep' => 'keep',
      'keep_another' => 1,
      'view_name' => 1,
      'view_display_id' => 1,
      'view_args' => 1,
      'view_path' => 1,
      'view_dom_id' => 1,
      'pager_element' => 1,
      'view_base_path' => 1,
      AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER => 1,
      FormBuilderInterface::AJAX_FORM_REQUEST => 1,
      MainContentViewSubscriber::WRAPPER_FORMAT => 1,
    ]));
    $this->container->set('request_stack', $request_stack);
    $view->destroy();
    $view->setDisplay('page_1');
    $view->setCurrentPage(2);
    $this->executeView($view, [1]);
    $this->assertSame('<a href="/page_1/1?name=John&amp;sort_by=created&amp;sort_order=ASC&amp;keep=keep&amp;keep_another=1&amp;page=1" class="views-display-link views-display-link-page_1 is-active">Page 1</a>', $this->renderDisplayLink($view, 'display_link_1'));
    $this->assertSame('<a href="/page_2/1?name=John&amp;sort_by=created&amp;sort_order=ASC&amp;keep=keep&amp;keep_another=1&amp;page=1" class="views-display-link views-display-link-page_2">Page 2</a>', $this->renderDisplayLink($view, 'display_link_2'));

    // Assert the validation adds warning messages when a display link is added
    // to a display with different filter criteria, sort criteria, pager
    // settings or contextual filters. Since all options are added to the
    // default display there currently should be no warning messages.
    $this->assertNoWarningMessages($view);

    // Assert the message are shown when changing the filter criteria of page_1.
    $filters = [
      'name' => [
        'id' => 'name',
        'table' => 'views_test_data',
        'field' => 'name',
        'relationship' => 'none',
        'operator' => '=',
        'value' => '',
        'exposed' => TRUE,
        'expose' => [
          'identifier' => 'name',
          'label' => 'Name',
        ],
      ],
    ];
    $view->displayHandlers->get('page_1')->overrideOption('filters', $filters);
    $this->assertWarningMessages($view, ['filters']);

    // Assert no messages are added after the default display is changed with
    // the same options.
    $view->displayHandlers->get('default')->overrideOption('filters', $filters);
    $this->assertNoWarningMessages($view);

    // Assert the message are shown when changing the sort criteria of page_1.
    $sorts = [
      'created' => [
        'id' => 'created',
        'table' => 'views_test_data',
        'field' => 'created',
        'relationship' => 'none',
        'order' => 'DESC',
        'exposed' => TRUE,
      ],
    ];
    $view->displayHandlers->get('page_1')->overrideOption('sorts', $sorts);
    $this->assertWarningMessages($view, ['sorts']);

    // Assert no messages are added after the default display is changed with
    // the same options.
    $view->displayHandlers->get('default')->overrideOption('sorts', $sorts);
    $this->assertNoWarningMessages($view);

    // Assert the message are shown when changing the sort criteria of page_1.
    $pager = [
      'type' => 'full',
      'options' => ['items_per_page' => 10],
    ];
    $view->displayHandlers->get('page_1')->overrideOption('pager', $pager);
    $this->assertWarningMessages($view, ['pager']);

    // Assert no messages are added after the default display is changed with
    // the same options.
    $view->displayHandlers->get('default')->overrideOption('pager', $pager);
    $this->assertNoWarningMessages($view);

    // Assert the message are shown when changing the contextual filters of
    // page_1.
    $arguments = [
      'id' => [
        'id' => 'id',
        'table' => 'views_test_data',
        'field' => 'id',
        'relationship' => 'none',
      ],
    ];
    $view->displayHandlers->get('page_1')->overrideOption('arguments', $arguments);
    $this->assertWarningMessages($view, ['arguments']);

    // Assert no messages are added after the default display is changed with
    // the same options.
    $view->displayHandlers->get('default')->overrideOption('arguments', $arguments);
    $this->assertNoWarningMessages($view);

    // Assert an error is shown when the display ID is not set.
    $display_link = [
      'display_link_3' => [
        'id' => 'display_link_3',
        'table' => 'views',
        'field' => 'display_link',
        'display_id' => '',
        'label' => 'Empty',
        'plugin_id' => 'display_link',
      ],
    ];
    $view->displayHandlers->get('page_1')->overrideOption('header', $display_link);
    $view->destroy();
    $view->setDisplay('page_1');
    $errors = $view->validate();
    $this->assertCount(1, $errors);
    $this->assertCount(1, $errors['page_1']);
    $this->assertSame('<em class="placeholder">Page 1</em>: The link in the <em class="placeholder">header</em> area has no configured display.', $errors['page_1'][0]->__toString());

    // Assert an error is shown when linking to a display ID that doesn't exist.
    $display_link['display_link_3']['display_id'] = 'non-existent';
    $view->displayHandlers->get('page_1')->overrideOption('header', $display_link);
    $view->destroy();
    $view->setDisplay('page_1');
    $errors = $view->validate();
    $this->assertCount(1, $errors);
    $this->assertCount(1, $errors['page_1']);
    $this->assertSame('<em class="placeholder">Page 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">non-existent</em> display which no longer exists.', $errors['page_1'][0]->__toString());

    // Assert an error is shown when linking to a display without a path.
    $display_link['display_link_3']['display_id'] = 'block_1';
    $view->displayHandlers->get('page_1')->overrideOption('header', $display_link);
    $view->destroy();
    $view->setDisplay('page_1');
    $errors = $view->validate();
    $this->assertCount(1, $errors);
    $this->assertCount(1, $errors['page_1']);
    $this->assertSame('<em class="placeholder">Page 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Block 1</em> display which does not have a path.', $errors['page_1'][0]->__toString());
  }

  /**
   * Assert the display options contains only path based displays.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   * @param string $display_link_id
   *   The display link ID to check the options for.
   *
   * @internal
   */
  protected function assertFormOptions(ViewExecutable $view, string $display_link_id): void {
    $form = [];
    $form_state = new FormState();
    /** @var \Drupal\views\Plugin\views\area\DisplayLink $display_handler */
    $display_handler = $view->display_handler->getHandler('header', $display_link_id);
    $display_handler->buildOptionsForm($form, $form_state);
    $this->assertTrue(isset($form['display_id']['#options']['page_1']));
    $this->assertTrue(isset($form['display_id']['#options']['page_2']));
    $this->assertFalse(isset($form['display_id']['#options']['block_1']));
  }

  /**
   * Assert the display links are correctly rendered for a display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   * @param string $display_id
   *   The display ID to check the links for.
   *
   * @internal
   */
  protected function assertRenderedDisplayLinks(ViewExecutable $view, string $display_id): void {
    $page_1_active = $display_id === 'page_1' ? ' is-active' : '';
    $page_2_active = $display_id === 'page_2' ? ' is-active' : '';

    $view->destroy();
    $view->setDisplay($display_id);
    $this->executeView($view);
    $this->assertSame('<a href="/page_1" class="views-display-link views-display-link-page_1' . $page_1_active . '">Page 1</a>', $this->renderDisplayLink($view, 'display_link_1'));
    $this->assertSame('<a href="/page_2" class="views-display-link views-display-link-page_2' . $page_2_active . '">Page 2</a>', $this->renderDisplayLink($view, 'display_link_2'));

    // Assert the exposed filters, pager and contextual links are passed
    // correctly in the links.
    $view->destroy();
    $view->setDisplay($display_id);
    $view->setExposedInput([
      'name' => 'John',
      'sort_by' => 'created',
      'sort_order' => 'ASC',
    ]);
    $view->setCurrentPage(2);
    $this->executeView($view, [1]);
    $this->assertSame('<a href="/page_1/1?name=John&amp;sort_by=created&amp;sort_order=ASC&amp;page=1" class="views-display-link views-display-link-page_1' . $page_1_active . '">Page 1</a>', $this->renderDisplayLink($view, 'display_link_1'));
    $this->assertSame('<a href="/page_2/1?name=John&amp;sort_by=created&amp;sort_order=ASC&amp;page=1" class="views-display-link views-display-link-page_2' . $page_2_active . '">Page 2</a>', $this->renderDisplayLink($view, 'display_link_2'));
  }

  /**
   * Render a display link.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to render the link for.
   * @param string $display_link_id
   *   The display link ID to render.
   *
   * @return string
   *   The rendered display link.
   */
  protected function renderDisplayLink(ViewExecutable $view, $display_link_id) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $display_link = $view->display_handler->getHandler('header', $display_link_id)->render();
    return $renderer->renderRoot($display_link)->__toString();
  }

  /**
   * Assert no warning messages are shown when all display are equal.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   *
   * @internal
   */
  protected function assertNoWarningMessages(ViewExecutable $view): void {
    $messenger = $this->container->get('messenger');

    $view->validate();
    $this->assertCount(0, $messenger->messagesByType(MessengerInterface::TYPE_WARNING));
  }

  /**
   * Assert the warning messages are shown after changing the page_1 display.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to check.
   * @param array $unequal_options
   *   An array of options that should be unequal.
   *
   * @throws \Exception
   *
   * @internal
   */
  protected function assertWarningMessages(ViewExecutable $view, array $unequal_options): void {
    $messenger = $this->container->get('messenger');

    // Create a list of options to check.
    // @see \Drupal\views\Plugin\views\area\DisplayLink::validate()
    $options = [
      'filters' => 'Filter criteria',
      'sorts' => 'Sort criteria',
      'pager' => 'Pager',
      'arguments' => 'Contextual filters',
    ];

    // Create a list of options to check.
    // @see \Drupal\views\Plugin\views\area\DisplayLink::validate()
    $unequal_options_text = implode(', ', array_intersect_key($options, array_flip($unequal_options)));

    $errors = $view->validate();
    $messages = $messenger->messagesByType(MessengerInterface::TYPE_WARNING);

    $this->assertCount(0, $errors);
    $this->assertCount(3, $messages);
    $this->assertSame('<em class="placeholder">Block 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 1</em> display which uses different settings than the <em class="placeholder">Block 1</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[0]->__toString());
    $this->assertSame('<em class="placeholder">Page 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 2</em> display which uses different settings than the <em class="placeholder">Page 1</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[1]->__toString());
    $this->assertSame('<em class="placeholder">Page 2</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 1</em> display which uses different settings than the <em class="placeholder">Page 2</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[2]->__toString());

    $messenger->deleteAll();

    // If the default display is shown in the UI, warnings should be shown for
    // this display as well.
    $this->config('views.settings')->set('ui.show.default_display', TRUE)->save();

    $errors = $view->validate();
    $messages = $messenger->messagesByType(MessengerInterface::TYPE_WARNING);

    $this->assertCount(0, $errors);
    $this->assertCount(4, $messages);
    $this->assertSame('<em class="placeholder">Default</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 1</em> display which uses different settings than the <em class="placeholder">Default</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[0]->__toString());
    $this->assertSame('<em class="placeholder">Block 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 1</em> display which uses different settings than the <em class="placeholder">Block 1</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[1]->__toString());
    $this->assertSame('<em class="placeholder">Page 1</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 2</em> display which uses different settings than the <em class="placeholder">Page 1</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[2]->__toString());
    $this->assertSame('<em class="placeholder">Page 2</em>: The link in the <em class="placeholder">header</em> area points to the <em class="placeholder">Page 1</em> display which uses different settings than the <em class="placeholder">Page 2</em> display for: <em class="placeholder">' . $unequal_options_text . '</em>. To make sure users see the exact same result when clicking the link, check that the settings are the same.', $messages[3]->__toString());

    $messenger->deleteAll();
    $this->config('views.settings')->set('ui.show.default_display', FALSE)->save();
  }

}
