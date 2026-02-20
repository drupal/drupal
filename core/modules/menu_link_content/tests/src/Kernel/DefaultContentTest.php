<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DefaultContent\Exporter;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\EventSubscriber\DefaultContentSubscriber;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests exporting menu links in YAML format.
 */
#[Group('menu_link_content')]
#[CoversClass(DefaultContentSubscriber::class)]
#[RunTestsInSeparateProcesses]
class DefaultContentTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'link',
    'menu_link_content',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * Tests exporting of menu link content.
   */
  public function testExportMenuLinkContent(): void {
    $this->installConfig(['filter', 'system']);
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->createContentType(['type' => 'page']);
    $parent = $this->createNode(['type' => 'page']);
    $child = $this->createNode(['type' => 'page']);
    \Drupal::service(MenuLinkManagerInterface::class)->rebuild();

    $parent_link = MenuLinkContent::create([
      'menu_name' => 'main',
      'link' => 'internal:' . $parent->toUrl()->toString(),
    ]);
    $parent_link->save();
    $child_link = MenuLinkContent::create([
      'menu_name' => 'main',
      'link' => 'internal:' . $child->toUrl()->toString(),
      'parent' => 'menu_link_content:' . $parent_link->uuid(),
    ]);
    $child_link->save();

    $logger = new TestLogger();
    \Drupal::service('logger.channel.default_content')->addLogger($logger);

    // If we export the child link, the parent should be one of its
    // dependencies.
    $data = (string) \Drupal::service(Exporter::class)->export($child_link);
    $data = Yaml::decode($data);
    $this->assertArrayHasKey($parent_link->uuid(), $data['_meta']['depends']);
    $this->assertEmpty($logger->records);

    // If we delete the parent link, exporting the child should log an error.
    $parent_link->delete();
    \Drupal::service(Exporter::class)->export($child_link);
    $predicate = function (array $record) use ($child_link, $parent_link): bool {
      return (
        $record['message'] === 'The parent (%parent) of menu link %uuid could not be loaded.' &&
        $record['context']['%parent'] === $parent_link->uuid() &&
        $record['context']['%uuid'] === $child_link->uuid()
      );
    };
    $this->assertTrue($logger->hasRecordThatPasses($predicate, RfcLogLevel::ERROR));
  }

}
