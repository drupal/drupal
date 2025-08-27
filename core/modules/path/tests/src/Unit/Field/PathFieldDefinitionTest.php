<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Unit\Field;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Tests\Core\Field\BaseFieldDefinitionTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Field\BaseFieldDefinition.
 */
#[CoversClass(BaseFieldDefinition::class)]
#[Group('path')]
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
  protected function getModuleAndPath(): array {
    return ['path', dirname(__DIR__, 4)];
  }

  /**
   * Tests get columns.
   *
   * @legacy-covers ::getColumns
   * @legacy-covers ::getSchema
   */
  public function testGetColumns(): void {
    $this->assertSame([], $this->definition->getColumns());
  }

}
