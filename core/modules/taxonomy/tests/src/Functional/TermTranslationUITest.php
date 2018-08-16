<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the Term Translation UI.
 *
 * @group taxonomy
 */
class TermTranslationUITest extends ContentTranslationUITestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language', 'content_translation', 'taxonomy'];

  protected function setUp() {
    $this->entityTypeId = 'taxonomy_term';
    $this->bundle = 'tags';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setupBundle() {
    parent::setupBundle();

    // Create a vocabulary.
    $this->vocabulary = Vocabulary::create([
      'name' => $this->bundle,
      'description' => $this->randomMachineName(),
      'vid' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ]);
    $this->vocabulary->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer taxonomy']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return ['name' => $this->randomMachineName()] + parent::getNewEntityValues($langcode);
  }

  /**
   * Returns an edit array containing the values to be posted.
   */
  protected function getEditValues($values, $langcode, $new = FALSE) {
    $edit = parent::getEditValues($values, $langcode, $new);

    // To be able to post values for the configurable base fields (name,
    // description) have to be suffixed with [0][value].
    foreach ($edit as $property => $value) {
      foreach (['name', 'description'] as $key) {
        if ($property == $key) {
          $edit[$key . '[0][value]'] = $value;
          unset($edit[$property]);
        }
      }
    }
    return $edit;
  }

  /**
   * {@inheritdoc}
   */
  public function testTranslationUI() {
    parent::testTranslationUI();

    // Make sure that no row was inserted for taxonomy vocabularies which do
    // not have translations enabled.
    $rows = db_query('SELECT tid, count(tid) AS count FROM {taxonomy_term_field_data} WHERE vid <> :vid GROUP BY tid', [':vid' => $this->bundle])->fetchAll();
    foreach ($rows as $row) {
      $this->assertTrue($row->count < 2, 'Term does not have translations.');
    }
  }

  /**
   * Tests translate link on vocabulary term list.
   */
  public function testTranslateLinkVocabularyAdminPage() {
    $this->drupalLogin($this->drupalCreateUser(array_merge(parent::getTranslatorPermissions(), ['access administration pages', 'administer taxonomy'])));

    $values = [
      'name' => $this->randomMachineName(),
    ];
    $translatable_tid = $this->createEntity($values, $this->langcodes[0], $this->vocabulary->id());

    // Create an untranslatable vocabulary.
    $untranslatable_vocabulary = Vocabulary::create([
      'name' => 'untranslatable_voc',
      'description' => $this->randomMachineName(),
      'vid' => 'untranslatable_voc',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ]);
    $untranslatable_vocabulary->save();

    $values = [
      'name' => $this->randomMachineName(),
    ];
    $untranslatable_tid = $this->createEntity($values, $this->langcodes[0], $untranslatable_vocabulary->id());

    // Verify translation links.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertResponse(200, 'The translatable vocabulary page was found.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/translations', 0, 'The translations link exists for a translatable vocabulary.');
    $this->assertLinkByHref('term/' . $translatable_tid . '/edit', 0, 'The edit link exists for a translatable vocabulary.');

    $this->drupalGet('admin/structure/taxonomy/manage/' . $untranslatable_vocabulary->id() . '/overview');
    $this->assertResponse(200);
    $this->assertLinkByHref('term/' . $untranslatable_tid . '/edit');
    $this->assertNoLinkByHref('term/' . $untranslatable_tid . '/translations');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('@title [%language translation]', [
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ]);
        $this->assertRaw($title);
      }
    }
  }

}
