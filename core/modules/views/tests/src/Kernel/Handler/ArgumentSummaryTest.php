<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Views;

/**
 * Tests the summary of results when an argument is not provided.
 *
 * @group views
 */
class ArgumentSummaryTest extends ViewsKernelTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_summary'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'taxonomy',
    'text',
    'user',
    'views',
  ];

  /**
   * Node type with an autocomplete tagging field.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected NodeTypeInterface $nodeType;

  /**
   * The vocabulary used for the test tag field.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected VocabularyInterface $tagVocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    // Create the content type with an autocomplete tagging field.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();

    // Create the vocabulary for the tag field.
    $this->tagVocabulary = Vocabulary::create([
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ]);
    $this->tagVocabulary->save();

    // Create the tag field itself.
    $handler_settings = [
      'target_bundles' => [
        $this->tagVocabulary->id() => $this->tagVocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField(
      'node',
      $this->nodeType->id(),
      'field_tags',
      NULL,
      'taxonomy_term',
      'default',
      $handler_settings,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    );
  }

  /**
   * Creates a term in the tag vocabulary.
   *
   * @return \Drupal\taxonomy\TermInterface $term
   */
  protected function createTag(): TermInterface {
    $tag = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => $this->tagVocabulary->id(),
    ]);
    $tag->save();
    return $tag;
  }

  /**
   * Tests the argument summary feature.
   */
  public function testArgumentSummary(): void {
    // Setup 2 tags.
    $tags = [];
    for ($i = 0; $i < 2; $i++) {
      $tags[$i] = $this->createTag();
    }

    // Setup 4 nodes with different tags.
    for ($i = 0; $i < 4; $i++) {
      Node::create([
        'type' => $this->nodeType->id(),
        'title' => $this->randomMachineName(),
        // For odd numbered nodes, use both tags, even only get 1 tag.
        'field_tags' => ($i % 2) ? $tags : [$tags[0]->id()],
      ])->save();
    }

    $view = Views::getView('test_argument_summary');
    $result = $view->preview('default');

    // For the purposes of this test, we don't care about any markup or
    // formatting, only that the summary is showing the tag labels and the
    // correct counts. So strip all tags and extra whitespace to make the
    // assertions more clear.
    $renderer = $this->container->get('renderer');
    $output = (string) $renderer->renderRoot($result);
    $output = trim(preg_replace('/\s+/', ' ', strip_tags($output)));

    // Output should show first tag on 4 nodes, the second tag on only 2.
    $this->assertStringContainsString($tags[0]->label() . ' (4)', $output);
    $this->assertStringContainsString($tags[1]->label() . ' (2)', $output);
  }

}
