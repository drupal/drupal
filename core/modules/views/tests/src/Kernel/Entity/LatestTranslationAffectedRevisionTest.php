<?php

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the 'Latest translation affected revision' filter.
 *
 * @group views
 */
class LatestTranslationAffectedRevisionTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_latest_translation_affected_revision_filter'];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'system',
    'language',
    'content_translation',
  ];

  /**
   * Tests the 'Latest revision' filter.
   */
  public function testLatestRevisionFilter() {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    ConfigurableLanguage::createFromLangcode('fr')->save();
    NodeType::create(['type' => 'article'])->save();
    $node = Node::create([
      'title' => 'Original translation - default revision',
      'type' => 'test',
    ]);
    $node->save();

    $translated = $node->addTranslation('fr', ['title' => 'French translation - default revision']);
    $translated->title = 'French translation - default revision';
    $translated->save();

    /** @var \Drupal\node\NodeInterface $pending */
    $pending = clone $node;
    $pending->setNewRevision(TRUE);
    $pending->isDefaultRevision(FALSE);
    $pending->title = 'Original translation - pending revision';
    $pending->save();

    /** @var \Drupal\node\NodeInterface $pending_translated */
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
