<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Plugin\RowEntityTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\Component\Utility\Xss;
use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;

/**
 * Tests the generic entity row plugin.
 *
 * @see \Drupal\views\Plugin\views\row\EntityRow
 */
class RowEntityTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'text', 'filter', 'field', 'entity', 'system', 'menu_link');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_row');

  /**
   * A string for assert raw and text helper methods.
   *
   * @var string
   */
  protected $content;

  public static function getInfo() {
    return array(
      'name' => 'Row: Entity',
      'description' => 'Tests the generic entity row plugin.',
      'group' => 'Views Plugins',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(array('taxonomy'));
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the entity row handler.
   */
  public function testEntityRow() {
    $vocab = entity_create('taxonomy_vocabulary', array('name' => $this->randomName(), 'vid' => strtolower($this->randomName())));
    $vocab->save();
    $term = entity_create('taxonomy_term', array('name' => $this->randomName(), 'vid' => $vocab->id() ));
    $term->save();

    $view = Views::getView('test_entity_row');
    $this->content = $view->preview();
    $this->content = drupal_render($this->content);

    $this->assertText($term->getName(), 'The rendered entity appears as row in the view.');

    // Tests the available view mode options.
    $form = array();
    $form_state = array();
    $form_state['view'] = $view->storage;
    $view->rowPlugin->buildOptionsForm($form, $form_state);

    $this->assertTrue(isset($form['view_mode']['#options']['default']), 'Ensure that the default view mode is available');
  }

  /**
   * Pass if the text is found in set string.
   *
   * @param string $text
   *   Text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool TRUE on pass, FALSE on fail.
   */
  protected function assertText($text, $message = '', $group = 'Other') {
    if (!$message) {
      $message = t('Raw "@raw" found', array('@raw' => $text));
    }
    return $this->assert(strpos(Xss::filter($this->content, array()), $text) !== FALSE, $message, $group);
  }

}
