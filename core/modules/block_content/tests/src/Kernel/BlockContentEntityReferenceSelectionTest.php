<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\block_content_test\Plugin\EntityReferenceSelection\TestSelection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests EntityReference selection handlers don't return non-reusable blocks.
 *
 * @see block_content_query_entity_reference_alter()
 *
 * @group block_content
 */
class BlockContentEntityReferenceSelectionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'block_content',
    'block_content_test',
    'system',
    'user',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Test reusable block.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockReusable;

  /**
   * Test non-reusable block.
   *
   * @var \Drupal\block_content\BlockContentInterface
   */
  protected $blockNonReusable;

  /**
   * Test selection handler.
   *
   * @var \Drupal\block_content_test\Plugin\EntityReferenceSelection\TestSelection
   */
  protected $selectionHandler;

  /**
   * Test block expectations.
   *
   * @var array
   */
  protected $expectations;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');

    // Create a block content type.
    $block_content_type = BlockContentType::create([
      'id' => 'spiffy',
      'label' => 'Mucho spiffy',
      'description' => "Provides a block type that increases your site's spiffiness by up to 11%",
    ]);
    $block_content_type->save();
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // And reusable block content entities.
    $this->blockReusable = BlockContent::create([
      'info' => 'Reusable Block',
      'type' => 'spiffy',
    ]);
    $this->blockReusable->save();
    $this->blockNonReusable = BlockContent::create([
      'info' => 'Non-reusable Block',
      'type' => 'spiffy',
      'reusable' => FALSE,
    ]);
    $this->blockNonReusable->save();

    $configuration = [
      'target_type' => 'block_content',
      'target_bundles' => ['spiffy' => 'spiffy'],
      'sort' => ['field' => '_none'],
    ];
    $this->selectionHandler = new TestSelection($configuration, '', '', $this->container->get('entity_type.manager'), $this->container->get('module_handler'), \Drupal::currentUser(), \Drupal::service('entity_field.manager'), \Drupal::service('entity_type.bundle.info'), \Drupal::service('entity.repository'));

    // Setup the 3 expectation cases.
    $this->expectations = [
      'both_blocks' => [
        'spiffy' => [
          $this->blockReusable->id() => $this->blockReusable->label(),
          $this->blockNonReusable->id() => $this->blockNonReusable->label(),
        ],
      ],
      'block_reusable' => ['spiffy' => [$this->blockReusable->id() => $this->blockReusable->label()]],
      'block_non_reusable' => ['spiffy' => [$this->blockNonReusable->id() => $this->blockNonReusable->label()]],
    ];
  }

  /**
   * Tests to make sure queries without the expected tags are not altered.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testQueriesNotAltered(): void {
    // Ensure that queries without all the tags are not altered.
    $query = $this->entityTypeManager->getStorage('block_content')
      ->getQuery()
      ->accessCheck(FALSE);
    $this->assertCount(2, $query->execute());

    $query = $this->entityTypeManager->getStorage('block_content')
      ->getQuery()
      ->accessCheck(FALSE);
    $query->addTag('block_content_access');
    $this->assertCount(2, $query->execute());

    $query = $this->entityTypeManager->getStorage('block_content')
      ->getQuery()
      ->accessCheck(FALSE);
    $query->addTag('entity_query_block_content');
    $this->assertCount(2, $query->execute());
  }

  /**
   * Tests with no conditions set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNoConditions(): void {
    $this->assertEquals(
      $this->expectations['block_reusable'],
      $this->selectionHandler->getReferenceableEntities()
    );

    $this->blockNonReusable->setReusable();
    $this->blockNonReusable->save();

    // Ensure that the block is now returned as a referenceable entity.
    $this->assertEquals(
      $this->expectations['both_blocks'],
      $this->selectionHandler->getReferenceableEntities()
    );
  }

  /**
   * Tests setting 'reusable' condition on different levels.
   *
   * @dataProvider fieldConditionProvider
   *
   * @throws \Exception
   */
  public function testFieldConditions($condition_type, $is_reusable): void {
    $this->selectionHandler->setTestMode($condition_type, $is_reusable);
    $this->assertEquals(
      $is_reusable ? $this->expectations['block_reusable'] : $this->expectations['block_non_reusable'],
      $this->selectionHandler->getReferenceableEntities()
    );
  }

  /**
   * Provides possible fields and condition types.
   */
  public static function fieldConditionProvider() {
    $cases = [];
    foreach (['base', 'group', 'nested_group'] as $condition_type) {
      foreach ([TRUE, FALSE] as $reusable) {
        $cases["$condition_type:" . ($reusable ? 'reusable' : 'non-reusable')] = [
          $condition_type,
          $reusable,
        ];
      }
    }
    return $cases;
  }

}
