<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class FieldEntityOperationsTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_operations'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'language', 'views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    // Create Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests entity operations field.
   */
  public function testEntityOperations(): void {
    // Add languages and refresh the container so the entity type manager will
    // have fresh data.
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

    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer nodes',
      'bypass node access',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('test-entity-operations');
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    foreach ($entities as $entity) {
      /** @var \Drupal\Core\Language\LanguageInterface $language */
      foreach ($entity->getTranslationLanguages() as $language) {
        $entity = $entity->getTranslation($language->getId());
        $operations = \Drupal::service('entity_type.manager')->getListBuilder('node')->getOperations($entity);
        $this->assertNotEmpty($operations);
        foreach ($operations as $operation) {
          $expected_destination = Url::fromUri('internal:/test-entity-operations')->toString();
          // Update destination property of the URL as generating it in the
          // test would by default point to the frontpage.
          $operation['url']->setOption('query', ['destination' => $expected_destination]);
          $this->assertSession()->elementsCount('xpath', "//ul[contains(@class, dropbutton)]/li/a[@href='{$operation['url']->toString()}' and text()='{$operation['title']}']", 1);
          // Entities which were created in Hungarian should link to the
          // Hungarian edit form, others to the English one (which has no path
          // prefix here).
          $base_path = \Drupal::request()->getBasePath();
          $parts = explode('/', str_replace($base_path, '', $operation['url']->toString()));
          $expected_prefix = ($language->getId() != 'en' ? $language->getId() : 'node');
          $this->assertEquals($expected_prefix, $parts[1], 'Entity operation links to the correct language for the entity.');
        }
      }
    }

    // Test that we can't enable click sorting on the operation field.
    $this->drupalGet('admin/structure/views/nojs/display/test_entity_operations/page_2/style_options');
    $this->assertSession()->fieldExists('style_options[info][title][sortable]');
    $this->assertSession()->fieldNotExists('style_options[info][operations][sortable]');
  }

  /**
   * Tests entity operations cacheability varies correctly.
   */
  public function testEntityOperationsCacheability(): void {
    // It's important that both users have the same role here so cacheability
    // doesn't vary on that.
    $role = $this->drupalCreateRole([
      'edit own article content',
    ]);
    $user1 = $this->drupalCreateUser([], 'User 1');
    $user1->addRole($role)->save();
    $user2 = $this->drupalCreateUser([], 'User 2');
    $user2->addRole($role)->save();

    $article = Node::create([
      'title' => 'An article',
      'type' => 'article',
      'uid' => $user1->id(),
    ]);
    $article->save();
    $article2 = Node::create([
      'title' => 'Another article',
      'type' => 'article',
      'uid' => $user2->id(),
    ]);
    $article2->save();

    $this->drupalLogin($user2);
    $this->drupalGet('test-entity-operations');

    // User 2 should only see the edit operation for article 2.
    $this->assertSession()->linkByHrefExists($article2->toUrl('edit-form')->toString());
    $this->assertSession()->linkByHrefNotExists($article->toUrl('edit-form')->toString());

    $this->drupalLogin($user1);
    $this->drupalGet('test-entity-operations');

    // User 1 should only see the edit operation for article 1. This ensures
    // cacheability has been set correctly on the entity operations.
    $this->assertSession()->linkByHrefNotExists($article2->toUrl('edit-form')->toString());
    $this->assertSession()->linkByHrefExists($article->toUrl('edit-form')->toString());

  }

}
