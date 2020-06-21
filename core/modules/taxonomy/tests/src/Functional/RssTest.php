<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\views\Views;

/**
 * Ensure that data added as terms appears in RSS feeds if "RSS Category" format
 * is selected.
 *
 * @group taxonomy
 */
class RssTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Name of the taxonomy term reference field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'bypass node access',
      'administer content types',
      'administer node display',
    ]));
    $this->vocabulary = $this->createVocabulary();
    $this->fieldName = 'taxonomy_' . $this->vocabulary->id();

    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $this->fieldName, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'options_select',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Tests that terms added to nodes are displayed in core RSS feed.
   *
   * Create a node and assert that taxonomy terms appear in rss.xml.
   */
  public function testTaxonomyRss() {
    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);

    // RSS display must be added manually.
    $this->drupalGet("admin/structure/types/manage/article/display");
    $edit = [
      "display_modes_custom[rss]" => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Change the format to 'RSS category'.
    $this->drupalGet("admin/structure/types/manage/article/display/rss");
    $edit = [
      "fields[taxonomy_" . $this->vocabulary->id() . "][type]" => 'entity_reference_rss_category',
      "fields[taxonomy_" . $this->vocabulary->id() . "][region]" => 'content',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Post an article.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit[$this->fieldName . '[]'] = $term1->id();
    $this->drupalPostForm('node/add/article', $edit, t('Save'));

    // Check that the term is displayed when the RSS feed is viewed.
    $this->drupalGet('rss.xml');
    $test_element = sprintf(
      '<category %s>%s</category>',
      'domain="' . $term1->toUrl('canonical', ['absolute' => TRUE])->toString() . '"',
      $term1->getName()
    );
    $this->assertRaw($test_element, 'Term is displayed when viewing the rss feed.');

    // Test that the feed icon exists for the term.
    $this->drupalGet("taxonomy/term/{$term1->id()}");
    $this->assertLinkByHref("taxonomy/term/{$term1->id()}/feed");

    // Test that the feed page exists for the term.
    $this->drupalGet("taxonomy/term/{$term1->id()}/feed");
    $assert = $this->assertSession();
    $assert->responseHeaderContains('Content-Type', 'application/rss+xml');
    // Ensure the RSS version is 2.0.
    $rss_array = $this->getSession()->getDriver()->find('rss');
    $this->assertEquals('2.0', reset($rss_array)->getAttribute('version'));

    // Check that the "Exception value" is disabled by default.
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertSession()->statusCodeEquals(404);
    // Set the exception value to 'all'.
    $view = Views::getView('taxonomy_term');
    $arguments = $view->getDisplay()->getOption('arguments');
    $arguments['tid']['exception']['value'] = 'all';
    $view->getDisplay()->overrideOption('arguments', $arguments);
    $view->storage->save();
    // Check the article is shown in the feed.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $raw_xml = '<title>' . $node->label() . '</title>';
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertRaw($raw_xml, "Raw text '$raw_xml' is found.");
    // Unpublish the article and check that it is not shown in the feed.
    $node->setUnpublished()->save();
    $this->drupalGet('taxonomy/term/all/feed');
    $this->assertNoRaw($raw_xml);
  }

}
