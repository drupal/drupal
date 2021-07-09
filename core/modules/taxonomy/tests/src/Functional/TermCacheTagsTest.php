<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\Tests\system\Functional\Entity\EntityWithUriCacheTagsTestBase;

/**
 * Tests the Taxonomy term entity's cache tags.
 *
 * @group taxonomy
 */
class TermCacheTagsTest extends EntityWithUriCacheTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Give anonymous users permission to view taxonomy terms, so that we can
    // verify the cache tags of cached versions of taxonomy term pages.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $user_role->grantPermission('access content');
    $user_role->save();
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Camelids',
      'vid' => 'camelids',
    ]);
    $vocabulary->save();

    // Create a "Llama" taxonomy term.
    $term = Term::create([
      'name' => 'Llama',
      'vid' => $vocabulary->id(),
    ]);
    $term->save();

    return $term;
  }

}
