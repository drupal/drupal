<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the 'Latest translation affected revision' filter.
 *
 * @group views
 */
class LatestTranslationAffectedRevisionTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_latest_translation_affected_revision_filter'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'system',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->drupalCreateContentType(['type' => 'article']);
  }

  /**
   * Tests the 'Latest revision' filter.
   */
  public function testLatestRevisionFilter() {
    $node = Node::create([
      'title' => 'Original translation - default revision',
      'type' => 'test',
    ]);
    $node->save();

    $translated = $node->addTranslation('fr', ['title' => 'French translation - default revision']);
    $translated->title = 'French translation - default revision';
    $translated->save();

    $pending = clone $node;
    $pending->setNewRevision(TRUE);
    $pending->isDefaultRevision(FALSE);
    $pending->title = 'Original translation - pending revision';
    $pending->save();

    $pending_translated = clone $translated;
    $pending_translated->setNewRevision(TRUE);
    $pending_translated->isDefaultRevision(FALSE);
    $pending_translated->title = 'French translation - pending revision';
    $pending_translated->save();

    $view = Views::getView('test_latest_translation_affected_revision_filter');
    $this->executeView($view);
    $this->assertIdenticalResultset($view, [
      ['title' => 'Original translation - pending revision'],
      ['title' => 'French translation - pending revision'],
    ], ['title' => 'title']);
  }

}
