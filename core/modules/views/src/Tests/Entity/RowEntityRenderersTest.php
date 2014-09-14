<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Entity\RowEntityRenderersTest.
 */

namespace Drupal\views\Tests\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the entity row renderers.
 *
 * @group views
 * @see \Drupal\views\Entity\Render\RendererBase
 */
class RowEntityRenderersTest extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'field', 'filter', 'text', 'node', 'user', 'language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_row_renderers');

  /**
   * An array of added languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', array('node_access'));
    $this->installConfig(array('node', 'language'));

    // The entity.node.canonical route must exist when nodes are rendered.
    $this->container->get('router.builder')->rebuild();

    $this->langcodes = array(\Drupal::languageManager()->getDefaultLanguage()->id);
    for ($i = 0; $i < 2; $i++) {
      $langcode = 'l' . $i;
      $this->langcodes[] = $langcode;
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Make sure we do not try to render non-existing user data.
    $node_type = NodeType::create(array('type' => 'test'));
    $node_type->setDisplaySubmitted(FALSE);
    $node_type->save();
  }

  /**
   * Tests the entity row renderers.
   */
  public function testRenderers() {
    $values = array();
    $controller = \Drupal::entityManager()->getStorage('node');
    $langcode_index = 0;

    for ($i = 0; $i < count($this->langcodes); $i++) {
      // Create a node with a different default language each time.
      $default_langcode = $this->langcodes[$langcode_index++];
      $node = $controller->create(array('type' => 'test', 'uid' => 0, 'langcode' => $default_langcode));
      // Ensure the default language is processed first.
      $langcodes = array_merge(array($default_langcode), array_diff($this->langcodes, array($default_langcode)));

      foreach ($langcodes as $langcode) {
        // Ensure we have a predictable result order.
        $values[$i][$langcode] = $i . '-' . $langcode . '-' . $this->randomMachineName();

        if ($langcode != $default_langcode) {
          $node->addTranslation($langcode, array('title' => $values[$i][$langcode]));
        }
        else {
          $node->setTitle($values[$i][$langcode]);
        }

        $node->save();
      }
    }

    $expected = array(
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
      $values[1]['en'],
      $values[1]['en'],
      $values[1]['en'],
      $values[2]['en'],
      $values[2]['en'],
      $values[2]['en'],
    );
    $this->assertTranslations('current_language_renderer', $expected, 'The current language renderer behaves as expected.');

    $expected = array(
      $values[0]['en'],
      $values[0]['en'],
      $values[0]['en'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[1]['l0'],
      $values[2]['l1'],
      $values[2]['l1'],
      $values[2]['l1'],
    );
    $this->assertTranslations('default_language_renderer', $expected, 'The default language renderer behaves as expected.');

    $expected = array(
      $values[0]['en'],
      $values[0]['l0'],
      $values[0]['l1'],
      $values[1]['en'],
      $values[1]['l0'],
      $values[1]['l1'],
      $values[2]['en'],
      $values[2]['l0'],
      $values[2]['l1'],
    );
    $this->assertTranslations('translation_language_renderer', $expected, 'The translation language renderer behaves as expected.');
  }

  /**
   * Checks that the view results match the expected values.
   *
   * @param string $renderer_id
   *   The id of the renderer to be tested.
   * @param array $expected
   *   An array of expected title translation values, one for each result row.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertTranslations($renderer_id, array $expected, $message = '', $group = 'Other') {
    $view = Views::getView('test_entity_row_renderers');
    $row_plugin = $view->getDisplay()->getPlugin('row');
    $row_plugin->options['rendering_language'] = $renderer_id;
    $view->preview();

    $result = TRUE;
    foreach ($view->result as $index => $row) {
      $build = $view->rowPlugin->render($row);
      $output = drupal_render($build);
      $result = strpos($output, $expected[$index]) !== FALSE;
      if (!$result) {
        break;
      }
    }

    return $this->assertTrue($result, $message, $group);
  }

}
