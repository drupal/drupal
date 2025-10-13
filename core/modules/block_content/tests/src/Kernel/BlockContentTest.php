<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Kernel;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\block_content\Hook\BlockContentHooks;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the block content.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentTest extends KernelTestBase {

  use UserCreationTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('block_content');
  }

  /**
   * Tests BlockContentType functionality.
   */
  public function testBlockContentType(): void {
    $type = BlockContentType::create([
      'id' => 'foo',
      'label' => 'Foo',
    ]);
    $this->assertSame('', $type->getDescription());
    $type->setDescription('Test description');
    $this->assertSame('Test description', $type->getDescription());
  }

  /**
   * Tests the editing links for BlockContentBlock.
   */
  public function testOperationLinks(): void {
    // Create a block content type.
    BlockContentType::create([
      'id' => 'spiffy',
      'label' => 'Very spiffy',
      'description' => "Provides a block type that increases your site's spiffy rating by up to 11%",
    ])->save();
    // And a block content entity.
    $block_content = BlockContent::create([
      'info' => 'Spiffy prototype',
      'type' => 'spiffy',
    ]);
    $block_content->save();
    $block = Block::create([
      'plugin' => 'block_content' . PluginBase::DERIVATIVE_SEPARATOR . $block_content->uuid(),
      'region' => 'content',
      'id' => 'machine_name',
      'theme' => 'stark',
    ]);

    // The anonymous user doesn't have the "administer block" permission.
    $blockContentEntityOperation = new BlockContentHooks();
    $this->assertEmpty($blockContentEntityOperation->entityOperation($block));

    $this->setUpCurrentUser(['uid' => 1], ['edit any spiffy block content', 'administer blocks']);

    // The admin user does have the "administer block" permission.
    $this->assertEquals([
      'block-edit' => [
        'title' => 'Edit block',
        'url' => $block_content->toUrl('edit-form')->setOptions([]),
        'weight' => 50,
      ],
    ], $blockContentEntityOperation->entityOperation($block));
  }

}
