<?php

namespace Drupal\Tests\content_translation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the content translation operations available in the content listing.
 *
 * @group content_translation
 */
class ContentTranslationOperationsTest extends NodeTestBase {

  /**
   * A base user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $baseUser1;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A base user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $baseUser2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'content_translation_test',
    'node',
    'views',
    'block',
  ];

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->container->get('state');

    // Enable additional languages.
    $langcodes = ['es', 'ast'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);

    $this->baseUser1 = $this->drupalCreateUser(['access content overview']);
    $this->baseUser2 = $this->drupalCreateUser([
      'access content overview',
      'create content translations',
      'update content translations',
      'delete content translations',
    ]);
  }

  /**
   * Test that the operation "Translate" is displayed in the content listing.
   */
  public function testOperationTranslateLink() {
    $node = $this->drupalCreateNode(['type' => 'article', 'langcode' => 'es']);
    // Verify no translation operation links are displayed for users without
    // permission.
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content');
    $this->assertSession()->linkByHrefNotExists('node/' . $node->id() . '/translations');
    $this->drupalLogout();
    // Verify there's a translation operation link for users with enough
    // permissions.
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content');
    $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/translations');

    // Ensure that an unintended misconfiguration of permissions does not open
    // access to the translation form, see https://www.drupal.org/node/2558905.
    $this->drupalLogout();
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'create content translations' => TRUE,
        'access content' => FALSE,
      ]
    );
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet($node->toUrl('drupal:content-translation-overview'));
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that the translation overview is also not accessible when the user
    // has 'access content', but the node is not published.
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'create content translations' => TRUE,
        'access content' => TRUE,
      ]
    );
    $node->setUnpublished()->save();
    $this->drupalGet($node->toUrl('drupal:content-translation-overview'));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    // Ensure the 'Translate' local task does not show up anymore when disabling
    // translations for a content type.
    $node->setPublished()->save();
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'administer content translation' => TRUE,
        'administer languages' => TRUE,
      ]
    );
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/translations');
    $this->drupalPostForm('admin/config/regional/content-language', ['settings[node][article][translatable]' => FALSE], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $node->id() . '/translations');
  }

  /**
   * Tests that operation access can be altered using hook_entity_access().
   */
  public function testOperationTranslateLinkWithEntityAccessHook() {
    $node = $this->drupalCreateNode(['type' => 'article', 'langcode' => 'es']);
    // Verify no translation operation links are displayed for users without
    // permission.
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content');
    $this->assertSession()->linkByHrefNotExists('node/' . $node->id() . '/translations');
    \Drupal::entityTypeManager()->getStorage('view')->load('content')->invalidateCaches();

    // Verify that access can be given using the entity access hook.
    $this->state->set('content_translation.entity_access.node', [
      'create translation' => TRUE,
      'update translation' => TRUE,
      'delete translation' => TRUE,
    ]);
    $this->drupalGet('admin/content');
    $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/translations');

    // Ensure that an unintended misconfiguration of permissions does not open
    // access to the translation form, see https://www.drupal.org/node/2558905.
    $this->drupalLogout();
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'create content translations' => TRUE,
        'access content' => FALSE,
      ]
    );
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet($node->toUrl('drupal:content-translation-overview'));
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that the translation overview is also not accessible when the user
    // has 'access content', but the node is not published.
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'create content translations' => TRUE,
        'access content' => TRUE,
      ]
    );
    $node->setUnpublished()->save();
    $this->drupalGet($node->toUrl('drupal:content-translation-overview'));
    $this->assertSession()->statusCodeEquals(403);

    // Ensure the 'Translate' local task does not show up anymore when disabling
    // translations for a content type.
    $node->setPublished()->save();
    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'administer content translation' => TRUE,
        'administer languages' => TRUE,
      ]
    );
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/translations');
    $this->drupalPostForm('admin/config/regional/content-language', ['settings[node][article][translatable]' => FALSE], 'Save configuration');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $node->id() . '/translations');
  }

  /**
   * Tests the access to the overview page for translations.
   *
   * @see content_translation_translate_access()
   */
  public function testContentTranslationOverviewAccess() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
    $user = $this->createUser(['create content translations', 'access content']);
    $this->drupalLogin($user);

    $node = $this->drupalCreateNode(['status' => FALSE, 'type' => 'article']);
    $this->assertFalse(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();

    $node->setPublished();
    $node->save();
    $this->assertTrue(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();

    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'access content' => FALSE,
      ]
    );

    $user = $this->createUser(['create content translations']);
    $this->drupalLogin($user);
    $this->assertFalse(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();
  }

  /**
   * Tests that overview access can be altered using hook_entity_access().
   *
   * @see content_translation_translate_access()
   * @see content_translation_entity_access()
   */
  public function testContentTranslationOverviewAccessWithEntityAccessHook() {
    $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
    $user = $this->createUser(['access content']);
    $this->drupalLogin($user);

    // User cannot access translations out of the box, but with an entity access
    // hook changing the access to Allowed, they do.
    $this->state->set('content_translation.entity_access.node', [
      'create translation' => TRUE,
      'update translation' => TRUE,
      'delete translation' => TRUE,
    ]);

    $node = $this->drupalCreateNode(['status' => FALSE, 'type' => 'article']);
    $this->assertFalse(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();

    $node->setPublished();
    $node->save();
    $this->assertTrue(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();

    user_role_change_permissions(
      Role::AUTHENTICATED_ID,
      [
        'access content' => FALSE,
      ]
    );

    $user = $this->createUser([]);
    $this->drupalLogin($user);
    $this->assertFalse(content_translation_translate_access($node)->isAllowed());
    $access_control_handler->resetCache();
  }

}
