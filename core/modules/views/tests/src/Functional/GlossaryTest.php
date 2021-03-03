<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\views\Tests\AssertViewsCacheTagsTrait;
use Drupal\views\Views;

/**
 * Tests glossary functionality of views.
 *
 * @group views
 */
class GlossaryTest extends ViewTestBase {

  use AssertViewsCacheTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the default glossary view.
   */
  public function testGlossaryView() {
    // Create a content type and add some nodes, with a non-random title.
    $type = $this->drupalCreateContentType();
    $nodes_per_char = [
      'd' => 1,
      'r' => 4,
      'u' => 10,
      'p' => 2,
      'a' => 3,
      'l' => 6,
    ];
    $nodes_by_char = [];
    foreach ($nodes_per_char as $char => $count) {
      $setting = [
        'type' => $type->id(),
      ];
      for ($i = 0; $i < $count; $i++) {
        $node = $setting;
        $node['title'] = $char . $this->randomString(3);
        $node = $this->drupalCreateNode($node);
        $nodes_by_char[$char][] = $node;
      }
    }

    // Execute glossary view
    $view = Views::getView('glossary');
    $view->setDisplay('attachment_1');
    $view->executeDisplay('attachment_1');

    // Check that the amount of nodes per char.
    foreach ($view->result as $item) {
      $this->assertEqual($nodes_per_char[$item->title_truncated], $item->num_records);
    }

    // Enable the glossary to be displayed.
    $view->storage->enable()->save();
    $this->container->get('router.builder')->rebuildIfNeeded();
    $url = Url::fromRoute('view.glossary.page_1');

    // Verify cache tags.
    $this->assertPageCacheContextsAndTags(
      $url,
      [
        'timezone',
        'languages:' . LanguageInterface::TYPE_CONTENT,
        'languages:' . LanguageInterface::TYPE_INTERFACE,
        'theme',
        'url',
        'user.node_grants:view',
        'user.permissions',
        'route',
      ],
      [
        'config:views.view.glossary',
        // Listed for letter 'a'
        'node:' . $nodes_by_char['a'][0]->id(), 'node:' . $nodes_by_char['a'][1]->id(), 'node:' . $nodes_by_char['a'][2]->id(),
        // Link for letter 'd'.
        'node:1',
        // Link for letter 'p'.
        'node:16',
        // Link for letter 'r'.
        'node:2',
        // Link for letter 'l'.
        'node:21',
        // Link for letter 'u'.
        'node:6',
        'node_list',
        'user:0',
        'user_list',
        'http_response',
        'rendered',
        // FinishResponseSubscriber adds this cache tag to responses that have
        // the 'user.permissions' cache context for anonymous users.
        'config:user.role.anonymous',
      ]
    );

    // Check the actual page response.
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    foreach ($nodes_per_char as $char => $count) {
      $href = Url::fromRoute('view.glossary.page_1', ['arg_0' => $char])->toString();
      $label = mb_strtoupper($char);
      // Get the summary link for a certain character. Filter by label and href
      // to ensure that both of them are correct.
      $result = $this->xpath('//a[contains(@href, :href) and normalize-space(text())=:label]/..', [':href' => $href, ':label' => $label]);
      $this->assertNotEmpty(count($result));
      // The rendered output looks like "<a href=''>X</a> | (count)" so let's
      // figure out the int.
      $result_count = explode(' ', trim(str_replace(['|', '(', ')'], '', $result[0]->getText())))[1];
      $this->assertEqual($count, $result_count, 'The expected number got rendered.');
    }
  }

}
