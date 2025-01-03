<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests taxonomy token replacement.
 *
 * @group taxonomy
 */
class TokenReplaceTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use NodeCreationTrait;
  use TaxonomyTestTrait;

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['filter']);
    $this->installConfig(['taxonomy']);

    $type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $type->save();

    $this->vocabulary = $this->createVocabulary();
    $this->fieldName = 'taxonomy_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName, '', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Creates some terms and a node, then tests the tokens generated from them.
   */
  public function testTaxonomyTokenReplacement(): void {
    $token_service = \Drupal::token();
    $language_interface = \Drupal::languageManager()->getCurrentLanguage();

    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    // Set $term1 as parent of $term2.
    $term2 = $this->createTerm($this->vocabulary, [
      'parent' => $term1->id(),
    ]);

    // Create node with term2.
    $this->createNode([
      'type' => 'article',
      $this->fieldName => $term2->id(),
    ]);

    // Generate and test sanitized tokens for term1.
    $tests = [];
    $tests['[term:tid]'] = $term1->id();
    $tests['[term:uuid]'] = $term1->uuid();
    $tests['[term:name]'] = $term1->getName();
    $tests['[term:description]'] = $term1->description->processed;
    $tests['[term:url]'] = $term1->toUrl('canonical', ['absolute' => TRUE])->toString();
    $tests['[term:node-count]'] = 0;
    $tests['[term:parent:name]'] = '[term:parent:name]';
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = $this->container->get('date.formatter');
    $tests['[term:changed:since]'] = $date_formatter->formatTimeDiffSince($term1->getChangedTime(), ['langcode' => $language_interface->getId()]);
    $tests['[term:vocabulary:name]'] = $this->vocabulary->label();
    $tests['[term:vocabulary]'] = $this->vocabulary->label();

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($term1);

    $metadata_tests = [];
    $metadata_tests['[term:tid]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:uuid]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:name]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:description]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:node-count]'] = $base_bubbleable_metadata;
    $metadata_tests['[term:parent:name]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[term:vocabulary:name]'] = $bubbleable_metadata->addCacheTags($this->vocabulary->getCacheTags());
    $metadata_tests['[term:vocabulary]'] = $bubbleable_metadata->addCacheTags($this->vocabulary->getCacheTags());
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[term:changed:since]'] = $bubbleable_metadata->setCacheMaxAge(0);

    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $token_service->replace($input, ['term' => $term1], ['langcode' => $language_interface->getId()], $bubbleable_metadata);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
      $this->assertEquals($metadata_tests[$input], $bubbleable_metadata);
    }

    // Generate and test sanitized tokens for term2.
    $tests = [];
    $tests['[term:tid]'] = $term2->id();
    $tests['[term:uuid]'] = $term2->uuid();
    $tests['[term:name]'] = $term2->getName();
    $tests['[term:description]'] = $term2->description->processed;
    $tests['[term:url]'] = $term2->toUrl('canonical', ['absolute' => TRUE])->toString();
    $tests['[term:node-count]'] = 1;
    $tests['[term:parent:name]'] = $term1->getName();
    $tests['[term:parent:url]'] = $term1->toUrl('canonical', ['absolute' => TRUE])->toString();
    $tests['[term:parent:parent:name]'] = '[term:parent:parent:name]';
    $tests['[term:changed:since]'] = $date_formatter->formatTimeDiffSince($term2->getChangedTime(), ['langcode' => $language_interface->getId()]);
    $tests['[term:vocabulary:name]'] = $this->vocabulary->label();

    // Test to make sure that we generated something for each token.
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['term' => $term2], ['langcode' => $language_interface->getId()]);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
    }

    // Generate and test sanitized tokens.
    $tests = [];
    $tests['[vocabulary:vid]'] = $this->vocabulary->id();
    $tests['[vocabulary:name]'] = $this->vocabulary->label();
    $tests['[vocabulary:description]'] = $this->vocabulary->getDescription();
    $tests['[vocabulary:node-count]'] = 1;
    $tests['[vocabulary:term-count]'] = 2;

    // Test to make sure that we generated something for each token.
    $this->assertNotContains(0, array_map('strlen', $tests), 'No empty tokens generated.');

    foreach ($tests as $input => $expected) {
      $output = $token_service->replace($input, ['vocabulary' => $this->vocabulary], ['langcode' => $language_interface->getId()]);
      $this->assertSame((string) $expected, (string) $output, "Failed test case: {$input}");
    }
  }

}
