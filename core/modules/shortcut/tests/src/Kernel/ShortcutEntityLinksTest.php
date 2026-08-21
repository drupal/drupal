<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\Filter\EntityLinks;
use Drupal\KernelTests\KernelTestBase;
use Drupal\shortcut\Entity\Shortcut;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the entity_links filter with shortcut entities.
 */
#[Group('shortcut')]
#[CoversClass(EntityLinks::class)]
#[RunTestsInSeparateProcesses]
class ShortcutEntityLinksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'shortcut',
    'link',
    'user',
    'system',
  ];

  /**
   * The entity_links filter.
   *
   * @var \Drupal\filter\Plugin\Filter\EntityLinks
   */
  protected EntityLinks $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
    $this->installEntitySchema('user');
    $this->installEntitySchema('shortcut');
    $this->installConfig(['shortcut']);

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('entity_links');
  }

  /**
   * @legacy-covers ::getUrl
   * @legacy-covers \Drupal\shortcut\Entity\ShortcutLinkTarget
   */
  public function testShortcutEntity(): void {
    // cspell:disable-next-line
    $path = '/user/logout?token=fzL0Ox4jS6qafdt6gzGzjWGb_hsR6kJ8L8E0D4hC5Mo';
    $shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Comments',
      'weight' => -20,
      'link' => [
        'uri' => "internal:$path",
      ],
    ]);
    $shortcut->save();

    $result = $this->filter->process(
      sprintf('<a data-entity-type="shortcut" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $shortcut->uuid()),
      'en'
    );

    $expected_result = (new FilterProcessResult())
      ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $path))
      ->setCacheTags(['config:shortcut.set.default'])
      ->setCacheContexts([])
      ->setCacheMaxAge(Cache::PERMANENT);

    $this->assertSame($expected_result->getProcessedText(), $result->getProcessedText());
    $this->assertEquals(CacheableMetadata::createFromObject($expected_result), CacheableMetadata::createFromObject($result));
  }

}
