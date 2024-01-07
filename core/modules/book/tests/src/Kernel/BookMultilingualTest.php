<?php

namespace Drupal\Tests\book\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests multilingual books.
 *
 * @group book
 */
class BookMultilingualTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The translation langcode.
   */
  const LANGCODE = 'de';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'book',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create the translation language.
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode(self::LANGCODE)->save();
    // Set up language negotiation.
    $config = $this->config('language.types');
    $config->set('configurable', [
      LanguageInterface::TYPE_INTERFACE,
      LanguageInterface::TYPE_CONTENT,
    ]);
    // The language being tested should only be available as the content
    // language so subsequent tests catch errors where the interface language
    // is used instead of the content language. For this, the interface
    // language is set to the user language and ::setCurrentLanguage() will
    // set the user language to the language not being tested.
    $config->set('negotiation', [
      LanguageInterface::TYPE_INTERFACE => [
        'enabled' => [LanguageNegotiationUser::METHOD_ID => 0],
      ],
      LanguageInterface::TYPE_CONTENT => [
        'enabled' => [LanguageNegotiationUrl::METHOD_ID => 0],
      ],
    ]);
    $config->save();
    $config = $this->config('language.negotiation');
    $config->set('url.source', LanguageNegotiationUrl::CONFIG_DOMAIN);
    $config->set('url.domains', [
      'en' => 'en.book.test.domain',
      self::LANGCODE => self::LANGCODE . '.book.test.domain',
    ]);
    $config->save();
    $this->container->get('kernel')->rebuildContainer();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('book', ['book']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'book', 'field']);
    $node_type = NodeType::create([
      'type' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ]);
    $node_type->save();
    $this->container->get('content_translation.manager')->setEnabled('node', $node_type->id(), TRUE);
    $book_config = $this->config('book.settings');
    $allowed_types = $book_config->get('allowed_types');
    $allowed_types[] = $node_type->id();
    $book_config->set('allowed_types', $allowed_types)->save();
    // To test every possible combination of root-child / child-child, two
    // trees are needed. The first level below the root needs to have two
    // leaves and similarly a second level is needed with two-two leaves each:
    //
    //        1
    //      /   \
    //     /     \
    //    2       3
    //   / \     / \
    //  /   \   /   \
    // 4     5 6     7
    //
    // These are the actual node IDs, these are enforced as auto increment is
    // not reliable.
    //
    // Similarly, the second tree root is node 8, the first two leaves are
    // 9 and 10, the third level is 11, 12, 13, 14.
    for ($root = 1; $root <= 8; $root += 7) {
      for ($i = 0; $i <= 6; $i++) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = Node::create([
          'title' => $this->randomString(),
          'type' => $node_type->id(),
        ]);
        $node->addTranslation(self::LANGCODE, [
          'title' => $this->randomString(),
        ]);
        switch ($i) {
          case 0:
            $node->book['bid'] = 'new';
            $node->book['pid'] = 0;
            $node->book['depth'] = 1;
            break;

          case 1:
          case 2:
            $node->book['bid'] = $root;
            $node->book['pid'] = $root;
            $node->book['depth'] = 2;
            break;

          case 3:
          case 4:
            $node->book['bid'] = $root;
            $node->book['pid'] = $root + 1;
            $node->book['depth'] = 3;
            break;

          case 5:
          case 6:
            $node->book['bid'] = $root;
            $node->book['pid'] = $root + 2;
            $node->book['depth'] = 3;
            break;
        }
        // This is necessary to make the table of contents consistent across
        // test runs.
        $node->book['weight'] = $i;
        $node->nid->value = $root + $i;
        $node->enforceIsNew();
        $node->save();
      }
    }
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));
  }

  /**
   * Tests various book manager methods return correct translations.
   *
   * @dataProvider langcodesProvider
   */
  public function testMultilingualBookManager(string $langcode) {
    $this->setCurrentLanguage($langcode);
    /** @var \Drupal\book\BookManagerInterface $bm */
    $bm = $this->container->get('book.manager');
    $books = $bm->getAllBooks();
    $this->assertNotEmpty($books);
    foreach ($books as $book) {
      $bid = (int) $book['bid'];
      $build = $bm->bookTreeOutput($bm->bookTreeAllData($bid));
      $items = $build['#items'];
      $this->assertBookItemIsCorrectlyTranslated($items[$bid], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 1], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 1]['below'][$bid + 3], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 1]['below'][$bid + 4], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 2], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 2]['below'][$bid + 5], $langcode);
      $this->assertBookItemIsCorrectlyTranslated($items[$bid]['below'][$bid + 2]['below'][$bid + 6], $langcode);
      $toc = $bm->getTableOfContents($bid, 4);
      // Root entry does not have an indent.
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid, '');
      // The direct children of the root have one indent.
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 1, '--');
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 2, '--');
      // Their children have two indents.
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 3, '----');
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 4, '----');
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 5, '----');
      $this->assertToCEntryIsCorrectlyTranslated($toc, $langcode, $bid + 6, '----');
      // $bid might be a string.
      $this->assertSame([$bid + 0, $bid + 1, $bid + 3, $bid + 4, $bid + 2, $bid + 5, $bid + 6], array_keys($toc));
    }
  }

  /**
   * Tests various book breadcrumb builder methods return correct translations.
   *
   * @dataProvider langcodesProvider
   */
  public function testMultilingualBookBreadcrumbBuilder(string $langcode) {
    $this->setCurrentLanguage($langcode);
    // Test a level 3 node.
    $nid = 7;
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($nid);
    $route = new Route('/node/{node}');
    $route_match = new RouteMatch('entity.node.canonical', $route, ['node' => $node], ['node' => $nid]);
    /** @var \Drupal\book\BookBreadcrumbBuilder $bbb */
    $bbb = $this->container->get('book.breadcrumb');
    $links = $bbb->build($route_match)->getLinks();
    $link = array_shift($links);
    $rendered_link = (string) Link::fromTextAndUrl($link->getText(), $link->getUrl())->toString();
    $this->assertStringContainsString("http://$langcode.book.test.domain/", $rendered_link);
    $link = array_shift($links);
    $this->assertNodeLinkIsCorrectlyTranslated(1, $link->getText(), $link->getUrl(), $langcode);
    $link = array_shift($links);
    $this->assertNodeLinkIsCorrectlyTranslated(3, $link->getText(), $link->getUrl(), $langcode);
    $this->assertEmpty($links);
  }

  /**
   * Tests the book export returns correct translations.
   *
   * @dataProvider langcodesProvider
   */
  public function testMultilingualBookExport(string $langcode) {
    $this->setCurrentLanguage($langcode);
    /** @var \Drupal\book\BookExport $be */
    $be = $this->container->get('book.export');
    /** @var \Drupal\book\BookManagerInterface $bm */
    $bm = $this->container->get('book.manager');
    $books = $bm->getAllBooks();
    $this->assertNotEmpty($books);
    foreach ($books as $book) {
      $contents = $be->bookExportHtml(Node::load($book['bid']))['#contents'][0];
      $this->assertSame($contents["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][0]["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][1]["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][0]["#children"][0]["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][0]["#children"][1]["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][1]["#children"][0]["#node"]->language()->getId(), $langcode);
      $this->assertSame($contents["#children"][1]["#children"][1]["#node"]->language()->getId(), $langcode);
    }
  }

  /**
   * Data provider for ::testMultilingualBooks().
   */
  public function langcodesProvider() {
    return [
      [self::LANGCODE],
      ['en'],
    ];
  }

  /**
   * Sets the current language.
   *
   * @param string $langcode
   *   The langcode. The content language will be set to this using the
   *   appropriate domain while the user language will be set to something
   *   else so subsequent tests catch errors where the interface language
   *   is used instead of the content language.
   */
  protected function setCurrentLanguage(string $langcode): void {
    \Drupal::requestStack()->push(Request::create("http://$langcode.book.test.domain/"));
    $language_manager = $this->container->get('language_manager');
    $language_manager->reset();
    $current_user = \Drupal::currentUser();
    $languages = $language_manager->getLanguages();
    unset($languages[$langcode]);
    $current_user->getAccount()->set('preferred_langcode', reset($languages)->getId());
    $this->assertNotSame($current_user->getPreferredLangcode(), $langcode);
  }

  /**
   * Asserts a book item is correctly translated.
   *
   * @param array $item
   *   A book tree item.
   * @param string $langcode
   *   The language code for the requested translation.
   *
   * @internal
   */
  protected function assertBookItemIsCorrectlyTranslated(array $item, string $langcode): void {
    $this->assertNodeLinkIsCorrectlyTranslated((int) $item['original_link']['nid'], $item['title'], $item['url'], $langcode);
  }

  /**
   * Asserts a node link is correctly translated.
   *
   * @param int $nid
   *   The node id.
   * @param string $title
   *   The expected title.
   * @param \Drupal\Core\Url $url
   *   The URL being tested.
   * @param string $langcode
   *   The language code.
   *
   * @internal
   */
  protected function assertNodeLinkIsCorrectlyTranslated(int $nid, string $title, Url $url, string $langcode): void {
    $node = Node::load($nid);
    $this->assertSame($node->getTranslation($langcode)->label(), $title);
    $rendered_link = (string) Link::fromTextAndUrl($title, $url)->toString();
    $this->assertStringContainsString("http://$langcode.book.test.domain/node/$nid", $rendered_link);
  }

  /**
   * Asserts one entry in the table of contents is correct.
   *
   * @param array $toc
   *   The entire table of contents array.
   * @param string $langcode
   *   The language code for the requested translation.
   * @param int $nid
   *   The node ID.
   * @param string $indent
   *   The indentation before the actual table of contents label.
   *
   * @internal
   */
  protected function assertToCEntryIsCorrectlyTranslated(array $toc, string $langcode, int $nid, string $indent): void {
    $node = Node::load($nid);
    $node_label = $node->getTranslation($langcode)->label();
    $this->assertSame($indent . ' ' . $node_label, $toc[$nid]);
  }

}
