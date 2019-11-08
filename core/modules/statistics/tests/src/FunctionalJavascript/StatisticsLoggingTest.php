<?php

namespace Drupal\Tests\statistics\FunctionalJavascript;

use Drupal\Core\Session\AccountInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\Role;

/**
 * Tests that statistics works.
 *
 * @group system
 */
class StatisticsLoggingTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'statistics', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Node for tests.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->config('statistics.settings')
      ->set('count_content_views', 1)
      ->save();

    Role::load(AccountInterface::ANONYMOUS_ROLE)
      ->grantPermission('view post access counter')
      ->save();

    // Add another language to enable multilingual path processor.
    ConfigurableLanguage::create(['id' => 'xx'])->save();
    $this->config('language.negotiation')->set('url.prefixes.en', 'en')->save();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->node = $this->drupalCreateNode();
  }

  /**
   * Tests that statistics works with different addressing variants.
   */
  public function testLoggingPage() {
    // At the first request, the page does not contain statistics counter.
    $this->assertNull($this->getStatisticsCounter('node/1'));
    $this->assertSame(1, $this->getStatisticsCounter('node/1'));
    $this->assertSame(2, $this->getStatisticsCounter('en/node/1'));
    $this->assertSame(3, $this->getStatisticsCounter('en/node/1'));
    $this->assertSame(4, $this->getStatisticsCounter('index.php/node/1'));
    $this->assertSame(5, $this->getStatisticsCounter('index.php/node/1'));
    $this->assertSame(6, $this->getStatisticsCounter('index.php/en/node/1'));
    $this->assertSame(7, $this->getStatisticsCounter('index.php/en/node/1'));
  }

  /**
   * Gets counter of views by path.
   *
   * @param string $path
   *   A path to node.
   *
   * @return int|null
   *   A counter of views. Returns NULL if the page does not contain statistics.
   */
  protected function getStatisticsCounter($path) {
    $this->drupalGet($path);
    // Wait while statistics module send ajax request.
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Resaving the node to call the hook_node_links_alter(), which is used to
    // update information on the page. See statistics_node_links_alter().
    $this->node->save();

    $field_counter = $this->getSession()->getPage()->find('css', '.statistics-counter');
    return $field_counter ? (int) explode(' ', $field_counter->getText())[0] : NULL;
  }

}
