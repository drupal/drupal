<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

// cspell:ignore Sofie Deutsch doo

use Drupal\ckeditor5\Controller\EntityLinkSuggestionsController;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\User;
use Drupal\editor\Entity\Editor;
use Drupal\node\Entity\NodeType;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5ValidationTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests entity link suggestions.
 */
#[Group('ckeditor5')]
#[CoversClass(EntityLinkSuggestionsController::class)]
#[RunTestsInSeparateProcesses]
class EntityLinkSuggestionTest extends KernelTestBase {

  use CKEditor5ValidationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ckeditor5',
    'ckeditor5_test',
    'filter',
    'editor',
    'system',
    'user',
    'datetime',
    'datetime_range',
    'language',
    'content_translation',
    'system',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
    // Avoid needing to install the Stark theme.
    $this->config('system.theme')->delete();

    // Create text format, associate CKEditor 5, validate.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <a href data-entity-type data-entity-uuid data-entity-metadata>',
          ],
        ],
        'entity_links' => [
          'status' => TRUE,
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'test_format',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
          ],
        ],
      ],
    ])->save();
    $this->assertExpectedCkeditor5Violations();

    // Create a node type for testing.
    $node_page_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $node_page_type->save();
    $node_article_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_article_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('date_format');
    $this->installSchema('node', ['node_access']);
    $this->container->get('content_translation.manager')->setEnabled('node', $node_page_type->id(), TRUE);
    $this->container->get('content_translation.manager')->setEnabled('node', $node_article_type->id(), TRUE);

    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $vocabulary = Vocabulary::create(['name' => 'Tags', 'vid' => 'tags']);
    $vocabulary->save();
    // Create an account with "f" in the username.
    $user = User::create([
      'name' => 'sofie',
      'uuid' => '966e5967-f19c-44b0-87b1-697441385b08',
      'created' => '694702320',
    ]);
    $user->addRole('create page content');
    $user->addRole('use text format test_format');
    $user->activate()->save();
    $this->container->get('current_user')->setAccount($user);

    // Create the translation language.
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('de')->save();

    // Load the test node entity.
    $nodePage = Node::create([
      'type' => 'page',
      'title' => 'foo',
      'uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ed',
    ]);
    $nodePage->setCreatedTime(1695058272);
    $nodePage->save();
    $translation = $nodePage->addTranslation('de', [
      'title' => 'Deutsch foo',
    ])->setCreatedTime(1695058272);
    $translation->save();

    $nodeArticle = Node::create([
      'type' => 'article',
      'title' => 'doo',
      'uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ef',
    ]);
    $nodeArticle->setCreatedTime(1695058372);
    $nodeArticle->save();
    $nodeArticleTranslation = $nodeArticle->addTranslation('de', [
      'title' => 'Deutsch doo',
    ])->setCreatedTime(1695058372);
    $nodeArticleTranslation->save();

    $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'tag',
      'uuid' => '966e5967-f19c-44b0-87b1-697441385b10',
    ]);
    $term->save();
    $termTranslation = $term->addTranslation('de', [
      'name' => 'tag DE',
    ]);
    $termTranslation->save();

    $term2 = Term::create([
      'vid' => $vocabulary->id(),
      'name' => 'doo term',
      'uuid' => '966e5967-f19c-44b0-87b1-697441385b11',
    ]);
    $term2->save();
    $termTranslation2 = $term2->addTranslation('de', [
      'name' => 'doo term DE',
    ]);
    $termTranslation2->save();
  }

  /**
   * Data provider.
   *
   * @return \Generator
   *   Test scenarios.
   */
  public static function providerEntityLinkSuggestions(): \Generator {
    $suggestion_node_1_en = [
      'description' => 'by sofie on Tue, 19 Sep 2023 - 03:31',
      'entity_type_id' => 'node',
      'entity_uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ed',
      'group' => 'Content - Basic page',
      'label' => 'foo',
      'path' => '/node/1',
    ];
    $suggestion_node_1_de = [
      'description' => 'by sofie on Tue, 19 Sep 2023 - 03:31',
      'entity_type_id' => 'node',
      'entity_uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ed',
      'group' => 'Content - Basic page',
      'label' => 'Deutsch foo',
      'path' => '/node/1',
    ];
    $suggestion_node_2_en = [
      'description' => 'by sofie on Tue, 19 Sep 2023 - 03:32',
      'entity_type_id' => 'node',
      'entity_uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ef',
      'group' => 'Content - Article',
      'label' => 'doo',
      'path' => '/node/2',
    ];
    $suggestion_node_2_de = [
      'description' => 'by sofie on Tue, 19 Sep 2023 - 03:32',
      'entity_type_id' => 'node',
      'entity_uuid' => '36c25329-6c3b-452e-82fa-e20c502f69ef',
      'group' => 'Content - Article',
      'label' => 'Deutsch doo',
      'path' => '/node/2',
    ];

    $suggestion_tag_1_en = [
      'description' => '',
      'entity_type_id' => 'taxonomy_term',
      'entity_uuid' => '966e5967-f19c-44b0-87b1-697441385b10',
      'group' => 'Taxonomy term - Tags',
      'label' => 'tag',
      'path' => '/taxonomy/term/1',
    ];

    $suggestion_tag_1_de = [
      'description' => '',
      'entity_type_id' => 'taxonomy_term',
      'entity_uuid' => '966e5967-f19c-44b0-87b1-697441385b10',
      'group' => 'Taxonomy term - Tags',
      'label' => 'tag DE',
      'path' => '/taxonomy/term/1',
    ];

    $suggestion_tag_2_en = [
      'description' => '',
      'entity_type_id' => 'taxonomy_term',
      'entity_uuid' => '966e5967-f19c-44b0-87b1-697441385b11',
      'group' => 'Taxonomy term - Tags',
      'label' => 'doo term',
      'path' => '/taxonomy/term/2',
    ];

    $suggestion_tag_2_de = [
      'description' => '',
      'entity_type_id' => 'taxonomy_term',
      'entity_uuid' => '966e5967-f19c-44b0-87b1-697441385b11',
      'group' => 'Taxonomy term - Tags',
      'label' => 'doo term DE',
      'path' => '/taxonomy/term/2',
    ];

    // "f", single result due to (different) suggestion restrictions.
    yield 'suggestions=nodes only, host entity type=node, host entity langcode=en, search term="f"' => [
      'f',
      'node',
      'en',
      [
        $suggestion_node_1_en,
      ],
    ];

    // "z", no result due to no nodes having title with "z".
    yield 'host entity type=node, host entity langcode=en, search term="z"' => [
      'z',
      'node',
      'en',
      [
        [
          'description' => 'No content suggestions found. This URL will be used as is.',
          'group' => 'No results',
          'label' => 'z',
          'href' => 'z',
        ],
      ],
    ];

    // "fo", single result, but different labels due to host entity langcode.
    yield 'host entity type=node, host entity langcode=en, search term="fo"' => [
      'fo',
      'node',
      'en',
      [
        $suggestion_node_1_en,
      ],
    ];
    yield 'host entity type=node, host entity langcode=de, search term="fo"' => [
      'fo',
      'node',
      'de',
      [
        $suggestion_node_1_de,
      ],
    ];

    // "tag", single result (taxonomy term), but different labels due to host entity langcode.
    yield 'host entity type=node, host entity langcode=en, search term="tag"' => [
      'tag',
      'node',
      'en',
      [
        $suggestion_tag_1_en,
      ],
    ];
    yield 'host entity type=node, host entity langcode=de, search term="tag"' => [
      'tag',
      'node',
      'de',
      [
        $suggestion_tag_1_de,
      ],
    ];

    // "oo", multi results, but different labels due to host entity langcode.
    yield 'host entity type=node, host entity langcode=en, search term="oo"' => [
      'oo',
      'node',
      'en',
      [
        $suggestion_node_1_en,
        $suggestion_node_2_en,
        $suggestion_tag_2_en,
      ],
    ];
    yield 'host entity type=node, host entity langcode=de, search term="oo"' => [
      'oo',
      'node',
      'de',
      [
        $suggestion_node_1_de,
        $suggestion_node_2_de,
        $suggestion_tag_2_de,
      ],
    ];

    // "Deutsch" (which appears only on a translation of an entity!), single
    // result, but different labels due to host entity langcode.
    yield 'host entity type=node, host entity langcode=en, search term="Deutsch"' => [
      'Deutsch',
      'node',
      'en',
      [
        $suggestion_node_1_en,
        $suggestion_node_2_en,
      ],
    ];
    yield 'host entity type=node, host entity langcode=de, search term="Deutsch"' => [
      'Deutsch',
      'node',
      'de',
      [
        $suggestion_node_1_de,
        $suggestion_node_2_de,
      ],
    ];
  }

  /**
   * Test the generated entity link suggestions based on editor configuration.
   */
  #[DataProvider('providerEntityLinkSuggestions')]
  public function testEntityLinkSuggestions(string $search, string $host_entity_type_id, string $host_entity_langcode, array $expected): void {
    // Set the given configuration for the entity link suggestions plugin.
    $editor = Editor::load('test_format');

    // Whatever configuration it is, it must be valid.
    $this->assertExpectedCkeditor5Violations();

    $controller = EntityLinkSuggestionsController::create($this->container);

    $request = Request::create("/irrelevant-in-kernel-test");
    $request->query->set('q', $search);
    $request->query->set('hostEntityTypeId', $host_entity_type_id);
    $request->query->set('hostEntityLangcode', $host_entity_langcode);
    $response = $controller->suggestions($request, $editor);
    $this->assertInstanceOf(JsonResponse::class, $response);

    $data = json_decode($response->getContent(), TRUE);

    // Perform assertions on the response data.
    $this->assertArrayHasKey('suggestions', $data);
    $this->assertIsArray($data['suggestions']);
    $this->assertSame($expected, $data['suggestions']);
  }

}
