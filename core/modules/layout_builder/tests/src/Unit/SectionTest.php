<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\layout_builder\Section;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\Section
 * @group layout_builder
 */
class SectionTest extends UnitTestCase {

  /**
   * The section object to test.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $section;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->section = new Section([
      'empty-region' => [],
      'some-region' => [
        'existing-uuid' => [
          'block' => [
            'id' => 'existing-block-id',
          ],
        ],
      ],
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ]);
  }

  /**
   * @covers ::__construct
   * @covers ::getValue
   */
  public function testGetValue() {
    $expected = [
      'empty-region' => [],
      'some-region' => [
        'existing-uuid' => [
          'block' => [
            'id' => 'existing-block-id',
          ],
        ],
      ],
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ];
    $result = $this->section->getValue();
    $this->assertSame($expected, $result);
  }

  /**
   * @covers ::getBlock
   */
  public function testGetBlockInvalidRegion() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid region');
    $this->section->getBlock('invalid-region', 'existing-uuid');
  }

  /**
   * @covers ::getBlock
   */
  public function testGetBlockInvalidUuid() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid UUID');
    $this->section->getBlock('some-region', 'invalid-uuid');
  }

  /**
   * @covers ::getBlock
   */
  public function testGetBlock() {
    $expected = ['block' => ['id' => 'existing-block-id']];

    $block = $this->section->getBlock('some-region', 'existing-uuid');
    $this->assertSame($expected, $block);
  }

  /**
   * @covers ::removeBlock
   */
  public function testRemoveBlock() {
    $this->section->removeBlock('some-region', 'existing-uuid');
    $expected = [
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ];
    $this->assertSame($expected, $this->section->getValue());
  }

  /**
   * @covers ::addBlock
   */
  public function testAddBlock() {
    $this->section->addBlock('some-region', 'new-uuid', []);
    $expected = [
      'empty-region' => [],
      'some-region' => [
        'new-uuid' => [],
        'existing-uuid' => [
          'block' => [
            'id' => 'existing-block-id',
          ],
        ],
      ],
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ];
    $this->assertSame($expected, $this->section->getValue());
  }

  /**
   * @covers ::insertBlock
   */
  public function testInsertBlock() {
    $this->section->insertBlock('ordered-region', 'new-uuid', [], 'first-uuid');
    $expected = [
      'empty-region' => [],
      'some-region' => [
        'existing-uuid' => [
          'block' => [
            'id' => 'existing-block-id',
          ],
        ],
      ],
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'new-uuid' => [],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ];
    $this->assertSame($expected, $this->section->getValue());
  }

  /**
   * @covers ::insertBlock
   */
  public function testInsertBlockInvalidRegion() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid region');
    $this->section->insertBlock('invalid-region', 'new-uuid', [], 'first-uuid');
  }

  /**
   * @covers ::insertBlock
   */
  public function testInsertBlockInvalidUuid() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid preceding UUID');
    $this->section->insertBlock('ordered-region', 'new-uuid', [], 'invalid-uuid');
  }

  /**
   * @covers ::updateBlock
   */
  public function testUpdateBlock() {
    $this->section->updateBlock('some-region', 'existing-uuid', [
      'block' => [
        'id' => 'existing-block-id',
        'settings' => [
          'foo' => 'bar',
        ],
      ],
    ]);

    $expected = [
      'empty-region' => [],
      'some-region' => [
        'existing-uuid' => [
          'block' => [
            'id' => 'existing-block-id',
            'settings' => [
              'foo' => 'bar',
            ],
          ],
        ],
      ],
      'ordered-region' => [
        'first-uuid' => [
          'block' => [
            'id' => 'first-block-id',
          ],
        ],
        'second-uuid' => [
          'block' => [
            'id' => 'second-block-id',
          ],
        ],
      ],
    ];
    $this->assertSame($expected, $this->section->getValue());
  }

  /**
   * @covers ::updateBlock
   */
  public function testUpdateBlockInvalidRegion() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid region');
    $this->section->updateBlock('invalid-region', 'new-uuid', []);
  }

  /**
   * @covers ::updateBlock
   */
  public function testUpdateBlockInvalidUuid() {
    $this->setExpectedException(\InvalidArgumentException::class, 'Invalid UUID');
    $this->section->updateBlock('ordered-region', 'new-uuid', []);
  }

}
