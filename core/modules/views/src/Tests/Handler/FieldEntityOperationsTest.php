<?php

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 *
 * @group views
 */
class FieldEntityOperationsTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_operations'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'language', 'views_ui'];

  public function setUp() {
    parent::setUp();

    // Create Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests entity operations field.
   */
  public function testEntityOperations() {
    // Add languages and refresh the container so the entity manager will have
    // fresh data.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->rebuildContainer();

    // Create some test entities. Every other entity is Hungarian while all
    // have a Spanish translation.
    $entities = [];
    for ($i = 0; $i < 5; $i++) {
      $entity = Node::create([
        'title' => $this->randomString(),
        'type' => 'article',
        'langcode' => $i % 2 === 0 ? 'hu' : 'en',
      ]);
      $entity->save();
      $translation = $entity->addTranslation('es');
      $translation->set('title', $entity->getTitle() . ' in Spanish');
      $translation->save();
      $entities[$i] = $entity;
    }

    $admin_user = $this->drupalCreateUser(['access administration pages', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('test-entity-operations');
    /** @var $entity \Drupal\entity_test\Entity\EntityTest */
    foreach ($entities as $entity) {
      /** @var \Drupal\Core\Language\LanguageInterface $language */
      foreach ($entity->getTranslationLanguages() as $language) {
        $entity = $entity->getTranslation($language->getId());
        $operations = \Drupal::entityManager()->getListBuilder('node')->getOperations($entity);
        $this->assertTrue(count($operations) > 0, 'There are operations.');
        foreach ($operations as $operation) {
          $expected_destination = Url::fromUri('internal:/test-entity-operations')->toString();
          $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[@href=:path and text()=:title]', [':path' => $operation['url']->toString() . '?destination=' . $expected_destination, ':title' => $operation['title']]);
          $this->assertEqual(count($result), 1, t('Found entity @operation link with destination parameter.', ['@operation' => $operation['title']]));
          // Entities which were created in Hungarian should link to the Hungarian
          // edit form, others to the English one (which has no path prefix here).
          $base_path = \Drupal::request()->getBasePath();
          $parts = explode('/', str_replace($base_path, '', $operation['url']->toString()));
          $expected_prefix = ($language->getId() != 'en' ? $language->getId() : 'node');
          $this->assertEqual($parts[1], $expected_prefix, 'Entity operation links to the correct language for the entity.');
        }
      }
    }

    // Test that we can't enable click sorting on the operation field.
    $this->drupalGet('admin/structure/views/nojs/display/test_entity_operations/page_2/style_options');
    $this->assertField('style_options[info][title][sortable]');
    $this->assertNoField('style_options[info][operations][sortable]');
  }

}
