<?php
/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigWhiteListTest.php.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests white-listing of entity properties.
 *
 * @group Theme
 */
class TwigWhiteListTest extends KernelTestBase {

  /**
   * Term for referencing.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * Twig environment.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'taxonomy', 'user', 'system', 'text', 'field', 'entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router', 'sequences'));
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ])->save();
    // Add a vocabulary so we can test different view modes.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ]);
    $vocabulary->save();

    // Add a term to the vocabulary.
    $this->term = Term::create([
      'name' => 'Sometimes people are just jerks',
      'description' => $this->randomMachineName(),
      'vid' => $vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $this->term->save();

    // Create a field.
    $handler_settings = array(
      'target_bundles' => array(
        $vocabulary->id() => $vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    // Add the term field.
    FieldStorageConfig::create(array(
      'field_name' => 'field_term',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => 1,
      'settings' => array(
        'target_type' => 'taxonomy_term',
      ),
    ))->save();
    FieldConfig::create(array(
      'field_name' => 'field_term',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Terms',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => $handler_settings,
      ),
    ))->save();

    // Show on default display and teaser.
    entity_get_display('node', 'page', 'default')
      ->setComponent('field_term', array(
        'type' => 'entity_reference_label',
      ))
      ->save();
    // Boot twig environment.
    $this->twig = \Drupal::service('twig');
  }

  /**
   * Tests white-listing of methods doesn't interfere with chaining.
   */
  public function testWhiteListChaining() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Some node mmk',
      'status' => 1,
      'field_term' => $this->term->id(),
    ]);
    $node->save();
    $this->setRawContent(twig_render_template(drupal_get_path('theme', 'test_theme') . '/templates/node.html.twig', ['node' => $node]));
    $this->assertText('Sometimes people are just jerks');
  }

}
