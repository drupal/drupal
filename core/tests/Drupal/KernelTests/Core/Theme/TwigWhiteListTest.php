<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
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
  protected static $modules = [
    'node',
    'taxonomy',
    'user',
    'system',
    'text',
    'field',
    'entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->installSchema('system', ['sequences']);
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
    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    // Add the term field.
    FieldStorageConfig::create([
      'field_name' => 'field_term',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_term',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Terms',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => $handler_settings,
      ],
    ])->save();

    // Show on default display and teaser.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page')
      ->setComponent('field_term', [
        'type' => 'entity_reference_label',
      ])
      ->save();
    // Boot twig environment.
    $this->twig = \Drupal::service('twig');
  }

  /**
   * Tests white-listing of methods doesn't interfere with chaining.
   */
  public function testWhiteListChaining() {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');
    $node = Node::create([
      'type' => 'page',
      'title' => 'Some node mmk',
      'status' => 1,
      'field_term' => $this->term->id(),
    ]);
    $node->save();
    $template = $environment->loadTemplate($this->getThemePath('test_theme') . '/templates/node.html.twig');
    $markup = $template->render(['node' => $node]);
    $this->setRawContent($markup);
    $this->assertText('Sometimes people are just jerks');
  }

}
