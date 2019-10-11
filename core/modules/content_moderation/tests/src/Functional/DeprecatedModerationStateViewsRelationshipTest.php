<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test the deprecated views relationships.
 *
 * @group content_moderation
 * @group legacy
 */
class DeprecatedModerationStateViewsRelationshipTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'content_moderation',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'moderated',
    ])->save();
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated');
    $workflow->save();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test how the deprecated relationships appear in the UI.
   */
  public function testReportDeprecatedModerationStateRelationships() {
    // Assert there is a warning in the UI to prevent new users from using the
    // feature.
    $this->drupalGet('admin/structure/views/nojs/add-handler/moderated_content/moderated_content/relationship');
    $this->assertSession()->pageTextContains('Deprecated: Content moderation state');
    $this->assertSession()->pageTextContains('Using a relationship to the Content Moderation State entity type has been deprecated');

    // Assert by default the deprecation warning does not appear in the status
    // report.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('Content Moderation State views relationship');

    // Install the views intended for testing the relationship and assert the
    // warning does appear.
    $this->container->get('module_installer')->install(['content_moderation_test_views']);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Content Moderation State views relationship');
    $this->assertSession()->linkExists('test_content_moderation_base_table_test');
    $this->assertSession()->linkByHrefExists('admin/structure/views/view/test_content_moderation_base_table_test');
  }

  /**
   * Test the deprecations are triggered when the deprecated code is executed.
   *
   * @expectedDeprecation Moderation state relationships are deprecated in drupal:8.8.0 and is removed in drupal:9.0.0. See https://www.drupal.org/node/3061099
   */
  public function testCodeDeprecationModerationStateRelationships() {
    $this->container->get('module_installer')->install(['content_moderation_test_views']);
    $this->drupalGet('test-content-moderation-base-table-test');
  }

}
