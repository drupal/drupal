<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Unit\Field;

use Drupal\Tests\Core\Field\BaseFieldDefinitionTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Field\BaseFieldDefinition
 * @group path
 */
class PathFieldDefinitionTest extends BaseFieldDefinitionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginId(): string {
    return 'path';
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleAndPath() {
    return ['path', dirname(__DIR__, 4)];
  }

  /**
   * @covers ::getColumns
   * @covers ::getSchema
   */
  public function testGetColumns(): void {
    $this->assertSame([], $this->definition->getColumns());
  }

}
