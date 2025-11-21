<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use ColinODell\PsrTestLogger\TestLogger;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\Filter\EntityLinks;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the behavior of generating entity URLs when using entity links in CKEditor.
 */
#[Group('filter')]
#[CoversClass(EntityLinks::class)]
#[RunTestsInSeparateProcesses]
class EntityLinksTest extends KernelTestBase {

  use PathAliasTestTrait;

  /**
   * The entity_links filter.
   *
   * @var \Drupal\filter\Plugin\Filter\EntityLinks
   */
  protected EntityLinks $filter;

  /**
   * The test logger.
   *
   * @var \ColinODell\PsrTestLogger\TestLogger
   */
  protected TestLogger $logger;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'entity_test',
    'path',
    'path_alias',
    'language',
    'file',
    'user',
    // @see ::testMediaEntity
    'system',
    'field',
    'image',
    'media',
    'media_test_source',
    // @see ::testMenuLinkContentEntity
    'link',
    'menu_link_content',
    // @see ::testShortcutEntity
    'shortcut',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
    // @see ::test
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('path_alias');

    // @see ::testFileEntity
    // @see ::testMediaEntity
    $this->installEntitySchema('file');

    // @see ::testMediaEntity
    $this->installEntitySchema('media');
    $this->installEntitySchema('media_type');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['media']);

    // @see ::testMenuLinkContentEntity
    $this->installEntitySchema('menu_link_content');

    // @see ::testShortcutEntity
    $this->installEntitySchema('shortcut');
    $this->installConfig(['shortcut']);

    // Add Swedish, Danish and Finnish.
    ConfigurableLanguage::createFromLangcode('sv')->save();
    ConfigurableLanguage::createFromLangcode('da')->save();
    ConfigurableLanguage::createFromLangcode('fi')->save();

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $manager */
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->get('entity_links');

    // Add test logger to the 'filter' channel to assert no exceptions occurred.
    $this->logger = new TestLogger();
    $this->container->get('logger.factory')
      ->get('filter')
      ->addLogger($this->logger);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Undo what the parent did, to allow testing path aliases in kernel tests.
    $container->getDefinition('path_alias.path_processor')
      ->addTag('path_processor_inbound')
      ->addTag('path_processor_outbound');
  }

  /**
   * @legacy-covers ::process
   */
  public function test(): void {
    $expected_aliases = [
      'da' => '/foo-da',
      'en' => '/foo-en',
      'fi' => '/foo-fi',
      'sv' => '/foo-sv',
    ];
    $expected_hrefs = $expected_aliases + [
      LanguageInterface::LANGCODE_DEFAULT => '/foo-en',
      LanguageInterface::LANGCODE_NOT_APPLICABLE => '/foo-en',
      LanguageInterface::LANGCODE_NOT_SPECIFIED => '/foo-en',
      LanguageInterface::LANGCODE_SITE_DEFAULT => '/foo-en',
    ];

    // Create an entity and add translations to that.
    /** @var \Drupal\entity_test\Entity\EntityTestMul $entity */
    $entity = EntityTestMul::create(['name' => $this->randomMachineName()]);
    $entity->addTranslation('sv', ['name' => $this->randomMachineName(), 'langcode' => 'sv']);
    $entity->addTranslation('da', ['name' => $this->randomMachineName(), 'langcode' => 'da']);
    $entity->addTranslation('fi', ['name' => $this->randomMachineName(), 'langcode' => 'fi']);
    $entity->save();

    // Assert the entity has a translation for every expected language.
    $this->assertSame(array_keys($expected_aliases), array_keys($entity->getTranslationLanguages()));

    // Create per-translation URL aliases.
    $canonical_url = $entity->toUrl()->toString(TRUE)->getGeneratedUrl();
    foreach ($expected_aliases as $langcode => $alias) {
      $this->createPathAlias($canonical_url, $alias, $langcode);
    }

    foreach ($expected_hrefs as $langcode => $expected_alias) {
      $expected_result = (new FilterProcessResult())
        ->setProcessedText(sprintf('<a href="%s">Link text</a>', $expected_alias))
        ->setCacheTags(['entity_test_mul:1'])
        ->setCacheContexts([])
        ->setCacheMaxAge(Cache::PERMANENT);

      // The expected href is generated.
      $this->assertFilterProcessResult(
        sprintf('<a data-entity-type="entity_test_mul" data-entity-uuid="%s">Link text</a>', $entity->uuid()),
        $langcode,
        $expected_result
      );
      // The existing href is overwritten with the expected value.
      $this->assertFilterProcessResult(
        sprintf('<a data-entity-type="entity_test_mul" data-entity-uuid="%s" href="something">Link text</a>', $entity->uuid()),
        $langcode,
        $expected_result
      );

      // The existing href is overwritten, but its customized query string and
      // fragment remain unchanged.
      $this->assertFilterProcessResult(
        sprintf('<a data-entity-type="entity_test_mul" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $entity->uuid()),
        $langcode,
        (new FilterProcessResult())
          ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $expected_alias))
          ->setCacheTags(['entity_test_mul:1'])
          ->setCacheContexts([])
          ->setCacheMaxAge(Cache::PERMANENT)
      );
    }
  }

  /**
   * @legacy-covers ::getUrl
   * @legacy-covers \Drupal\file\Entity\FileLinkTarget
   */
  public function testFileEntity(): void {
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'status' => FileInterface::STATUS_PERMANENT,
    ]);
    $file->save();

    $this->assertFilterProcessResult(
      sprintf('<a data-entity-type="file" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $file->uuid()),
      'en',
      (new FilterProcessResult())
        ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $file->createFileUrl(TRUE)))
        ->setCacheTags(['file:1'])
        ->setCacheContexts([])
        ->setCacheMaxAge(Cache::PERMANENT)
    );
  }

  /**
   * @legacy-covers ::getUrl
   * @legacy-covers \Drupal\media\Entity\MediaLinkTarget
   *
   * @param bool $standalone_url_setting
   *   Whether the standalone_url setting is off (Drupal's default) or on.
   * @param string $media_source
   *   Which media source to use.
   * @param array $media_entity_values
   *   Which values to assign to the media entity.
   * @param string $expected_url
   *   The expected URL.
   * @param string[] $expected_cache_tags
   *   The expected cache tags.
   */
  #[DataProvider('providerTestMediaEntity')]
  public function testMediaEntity(bool $standalone_url_setting, string $media_source, array $media_entity_values, string $expected_url, array $expected_cache_tags): void {
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', $standalone_url_setting)
      ->save();
    // Create media type using the given source plugin.
    $media_type = MediaType::create([
      'label' => 'test',
      'id' => 'test',
      'description' => 'Test type.',
      'source' => $media_source,
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    $source_field->getFieldStorageDefinition()->save();
    $source_field->save();
    $media_type->set('source_configuration', [
      'source_field' => $source_field->getName(),
    ])->save();
    // @see \Drupal\media\Plugin\media\Source\File
    if ($media_source === 'file') {
      $file = File::create([
        'uid' => 1,
        'filename' => 'druplicon.txt',
        'uri' => 'public://druplicon.txt',
        'filemime' => 'text/plain',
        'status' => FileInterface::STATUS_PERMANENT,
      ]);
      $file->save();
    }
    $media = Media::create([
      'bundle' => 'test',
      $source_field->getName() => $media_entity_values,
    ]);
    $media->save();

    $expected_url = str_replace('<SITE_DIRECTORY>', $this->siteDirectory, $expected_url);
    $this->assertFilterProcessResult(
      sprintf('<a data-entity-type="media" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $media->uuid()),
      'en',
      (new FilterProcessResult())
        ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $expected_url))
        ->setCacheTags($expected_cache_tags)
        ->setCacheContexts([])
        ->setCacheMaxAge(Cache::PERMANENT)
    );
  }

  /**
   * Data provider for testMediaEntity.
   */
  public static function providerTestMediaEntity(): array {
    return [
      [TRUE, 'file', ['target_id' => 1], '/media/1', ['media:1']],
      [FALSE, 'file', ['target_id' => 1], '/<SITE_DIRECTORY>/files/druplicon.txt', ['file:1', 'media:1']],
      [TRUE, 'oembed:video', ['value' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'], '/media/1', ['media:1']],
      [
        FALSE,
        'oembed:video',
        ['value' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ['media:1'],
      ],
      [TRUE, 'test', ['value' => 'foobar'], '/media/1', ['media:1']],
      [FALSE, 'test', ['value' => 'foobar'], '', ['media:1']],
    ];
  }

  /**
   * @legacy-covers ::getUrl
   * @legacy-covers \Drupal\menu_link_content\Entity\MenuLinkContentLinkTarget
   */
  public function testMenuLinkContentEntity(): void {
    $link = 'https://nl.wikipedia.org/wiki/Llama';

    $menu_link_content = MenuLinkContent::create([
      'id' => 'llama',
      'title' => 'Llama Gabilondo',
      'description' => 'Llama Gabilondo',
      'link' => $link,
      'weight' => 0,
      'menu_name' => 'main',
    ]);
    $menu_link_content->save();

    $this->assertFilterProcessResult(
      sprintf('<a data-entity-type="menu_link_content" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $menu_link_content->uuid()),
      'en',
      (new FilterProcessResult())
        ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $link))
        ->setCacheTags(['menu_link_content:1'])
        ->setCacheContexts([])
        ->setCacheMaxAge(Cache::PERMANENT)
    );
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

    $this->assertFilterProcessResult(
      sprintf('<a data-entity-type="shortcut" data-entity-uuid="%s" href="something?query=string#fragment">Link text</a>', $shortcut->uuid()),
      'en',
      (new FilterProcessResult())
        ->setProcessedText(sprintf('<a href="%s?query=string#fragment">Link text</a>', $path))
        ->setCacheTags(['config:shortcut.set.default'])
        ->setCacheContexts([])
        ->setCacheMaxAge(Cache::PERMANENT)
    );
  }

  /**
   * Asserts an input string + langcode yield the expected FilterProcessResult.
   *
   * @param string $input
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\FilterProcessResult $expected_result
   *   The expected filtered result.
   */
  private function assertFilterProcessResult(string $input, string $langcode, FilterProcessResult $expected_result): void {
    $result = $this->filter->process($input, $langcode);
    // No exceptions should have occurred.
    $this->assertSame([], $this->logger->records);
    // Assert both the processed text and the associated cacheability.
    $this->assertSame($expected_result->getProcessedText(), $result->getProcessedText());
    $this->assertEquals(CacheableMetadata::createFromObject($expected_result), CacheableMetadata::createFromObject($result));
  }

}
