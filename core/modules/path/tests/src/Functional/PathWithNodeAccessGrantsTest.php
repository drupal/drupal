<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Functional;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;

/**
 * Confirm that paths work with node access grants implementations.
 *
 * @group path
 */
class PathWithNodeAccessGrantsTest extends PathTestBase {

  use ContentTranslationTestTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'locale',
    'locale_test',
    'content_translation',
    'content_moderation',
    'path_test_node_grants',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a workflow for basic page.
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');

    // Login as admin user to configure language detection and selection.
    $admin_user = $this->drupalCreateUser([
      'edit any page content',
      'create page content',
      'administer url aliases',
      'create url aliases',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
    // Enable French language.
    static::createLanguageFromLangcode('fr');
    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');
    // Enable translation for page node.
    static::enableContentTranslation('node', 'page');
    static::setFieldTranslatable('node', 'page', 'body', TRUE);

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions['path']->isTranslatable(), 'Node path is translatable.');
    $this->assertTrue($definitions['body']->isTranslatable(), 'Node body is translatable.');
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  public function testAliasTranslation() : void {
    // Rebuild the permissions to update 'node_access' table.
    node_access_rebuild();
    $alias = $this->randomMachineName();
    $permissions = [
      'access administration pages',
      'view any unpublished content',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'create content translations',
      'create page content',
      'create url aliases',
      'edit any page content',
      'translate any entity',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    // Create a node, add URL alias and publish it.
    $this->drupalGet('node/add/page');
    $edit['title[0][value]'] = 'test';
    $edit['path[0][alias]'] = '/' . $alias;
    $edit['moderation_state[0][state]'] = 'published';
    $this->submitForm($edit, 'Save');
    // Add french translation.
    $this->drupalGet('node/1/translations');
    $this->clickLink('Add');
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save (this translation)');
    // Translation should be saved.
    $this->assertSession()->pageTextContains('Basic page test has been updated.');
    // There shouldn't be any validation errors.
    $this->assertSession()->pageTextNotContains("Either the path '/node/1' is invalid or you do not have access to it.");
    // Translation should be saved with the given alias.
    $this->container->get('path_alias.manager')->cacheClear();
    $translation_alias = $this->container->get('path_alias.manager')->getAliasByPath('/node/1', 'fr');
    $this->assertSame('/' . $alias, $translation_alias);
  }

}
