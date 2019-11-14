<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the core Drupal\views\Plugin\views\field\LinkBase handler.
 *
 * @group views
 */
class FieldEntityLinkBaseTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_link_base_links'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add languages and refresh the container so the entity type manager will
    // have fresh data.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->rebuildContainer();

    // Create an English and Hungarian nodes and add a Spanish translation.
    foreach (['en', 'hu'] as $langcode) {
      $entity = Node::create([
        'title' => $this->randomMachineName(),
        'type' => 'article',
        'langcode' => $langcode,
      ]);
      $entity->save();
      $translation = $entity->addTranslation('es');
      $translation->set('title', $entity->getTitle() . ' in Spanish');
      $translation->save();
    }

    $this->drupalLogin($this->rootUser);

  }

  /**
   * Tests entity link fields.
   */
  public function testEntityLink() {
    $this->drupalGet('test-link-base-links');
    $session = $this->assertSession();

    // Tests 'Link to Content'.
    $session->linkByHrefExists('/node/1');
    $session->linkByHrefExists('/es/node/1');
    $session->linkByHrefExists('/hu/node/2');
    $session->linkByHrefExists('/es/node/2');

    // Tests 'Link to delete Content'.
    $session->linkByHrefExists('/node/1/delete');
    $session->linkByHrefExists('/es/node/1/delete');
    $session->linkByHrefExists('/hu/node/2/delete');
    $session->linkByHrefExists('/es/node/2/delete');

    // Tests 'Link to edit Content'.
    $session->linkByHrefExists('/node/1/edit');
    $session->linkByHrefExists('/es/node/1/edit');
    $session->linkByHrefExists('/hu/node/2/edit');
    $session->linkByHrefExists('/es/node/2/edit');

    // Tests the second 'Link to Content' rendered as text.
    $session->pageTextContains('/node/1');
    $session->pageTextContains('/es/node/1');
    $session->pageTextContains('/hu/node/2');
    $session->pageTextContains('/es/node/2');
  }

}
