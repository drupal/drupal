<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\File\MimeType;

use Drupal\Core\File\MimeType\MimeTypeMap;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MIME type mapper to extension.
 */
#[CoversClass(MimeTypeMap::class)]
#[Group('File')]
class MimeTypeMapTest extends UnitTestCase {

  /**
   * The default MIME type map under test.
   */
  protected MimeTypeMap $map;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->map = new MimeTypeMap();
  }

  /**
   * Tests add mapping.
   */
  public function testAddMapping(): void {
    $this->map->addMapping('image/gif', 'gif');
    $this->assertEquals(
      'image/gif',
      $this->map->getMimeTypeForExtension('gif')
    );

    $this->map->addMapping('image/jpeg', 'jpeg');
    $this->assertEquals(
      'image/jpeg',
      $this->map->getMimeTypeForExtension('jpeg')
    );
  }

  /**
   * Tests remove mapping.
   */
  public function testRemoveMapping(): void {
    $this->assertTrue($this->map->removeMapping('image/jpeg', 'jpg'));
    $this->assertNull($this->map->getMimeTypeForExtension('jpg'));
    $this->assertFalse($this->map->removeMapping('bar', 'foo'));
  }

  /**
   * Tests remove mime type.
   */
  public function testRemoveMimeType(): void {
    $this->assertTrue($this->map->removeMimeType('image/jpeg'));
    $this->assertNull($this->map->getMimeTypeForExtension('jpg'));
    $this->assertFalse($this->map->removeMimeType('foo/bar'));
  }

  /**
   * Tests list mime types.
   */
  public function testListMimeTypes(): void {
    $mimeTypes = $this->map->listMimeTypes();
    $this->assertContains('application/java-archive', $mimeTypes);
    $this->assertContains('image/jpeg', $mimeTypes);
  }

  /**
   * Tests has mime type.
   */
  public function testHasMimeType(): void {
    $this->assertTrue($this->map->hasMimeType('image/jpeg'));
    $this->assertFalse($this->map->hasMimeType('foo/bar'));
  }

  /**
   * Tests get mime type for extension.
   */
  public function testGetMimeTypeForExtension(): void {
    $this->assertSame('image/jpeg', $this->map->getMimeTypeForExtension('jpe'));
  }

  /**
   * Tests get extensions for mime type.
   */
  public function testGetExtensionsForMimeType(): void {
    $this->assertEquals(['jpe', 'jpeg', 'jpg'],
      $this->map->getExtensionsForMimeType('image/jpeg'));
  }

  /**
   * Tests list extension.
   *
   * @legacy-covers ::listExtensions
   */
  public function testListExtension(): void {
    $extensions = $this->map->listExtensions();
    $this->assertContains('jar', $extensions);
    $this->assertContains('jpg', $extensions);
  }

  /**
   * Tests has extension.
   */
  public function testHasExtension(): void {
    $this->assertTrue($this->map->hasExtension('jpg'));
    $this->assertFalse($this->map->hasExtension('foo'));
  }

}
