<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the delete multiple confirmation form.
 *
 * @group Entity
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DeleteMultipleFormTest extends BrowserTestBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'user', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    EntityTestBundle::create([
      'id' => 'default',
      'label' => 'Default',
    ])->save();
    $this->account = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests the delete form for translatable entities.
   */
  public function testTranslatableEntities() {
    ConfigurableLanguage::create(['id' => 'es'])->save();
    ConfigurableLanguage::create(['id' => 'fr'])->save();

    $selection = [];

    $entity1 = EntityTestMulRevPub::create(['type' => 'default', 'name' => 'entity1']);
    $entity1->addTranslation('es', ['name' => 'entity1 spanish']);
    $entity1->addTranslation('fr', ['name' => 'entity1 french']);
    $entity1->save();
    $selection[$entity1->id()]['en'] = 'en';

    $entity2 = EntityTestMulRevPub::create(['type' => 'default', 'name' => 'entity2']);
    $entity2->addTranslation('es', ['name' => 'entity2 spanish']);
    $entity2->addTranslation('fr', ['name' => 'entity2 french']);
    $entity2->save();
    $selection[$entity2->id()]['es'] = 'es';
    $selection[$entity2->id()]['fr'] = 'fr';

    $entity3 = EntityTestMulRevPub::create(['type' => 'default', 'name' => 'entity3']);
    $entity3->addTranslation('es', ['name' => 'entity3 spanish']);
    $entity3->addTranslation('fr', ['name' => 'entity3 french']);
    $entity3->save();
    $selection[$entity3->id()]['fr'] = 'fr';

    // This entity will be inaccessible because of
    // Drupal\entity_test\EntityTestAccessControlHandler.
    $entity4 = EntityTestMulRevPub::create(['type' => 'default', 'name' => 'forbid_access']);
    $entity4->save();
    $selection[$entity4->id()]['en'] = 'en';

    // Add the selection to the tempstore just like DeleteAction would.
    $tempstore = \Drupal::service('tempstore.private')->get('entity_delete_multiple_confirm');
    $tempstore->set($this->account->id() . ':entity_test_mulrevpub', $selection);

    $this->drupalGet('/entity_test/delete');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', '.page-title', 'Are you sure you want to delete these test entity - revisions, data table, and published interface entities?');
    $list_selector = '#entity-test-mulrevpub-delete-multiple-confirm-form > div.item-list > ul';
    $assert->elementTextContains('css', $list_selector, 'entity1 (Original translation) - The following test entity - revisions, data table, and published interface translations will be deleted:');
    $assert->elementTextContains('css', $list_selector, 'entity2 spanish');
    $assert->elementTextContains('css', $list_selector, 'entity2 french');
    $assert->elementTextNotContains('css', $list_selector, 'entity3 spanish');
    $assert->elementTextContains('css', $list_selector, 'entity3 french');
    $delete_button = $this->getSession()->getPage()->findButton('Delete');
    $delete_button->click();
    $assert = $this->assertSession();
    $assert->addressEquals('/user/' . $this->account->id());
    $assert->responseContains('Deleted 6 items.');
    $assert->responseContains('1 item has not been deleted because you do not have the necessary permissions.');

    \Drupal::entityTypeManager()->getStorage('entity_test_mulrevpub')->resetCache();
    $remaining_entities = EntityTestMulRevPub::loadMultiple([$entity1->id(), $entity2->id(), $entity3->id(), $entity4->id()]);
    $this->assertCount(3, $remaining_entities);
  }

  /**
   * Tests the delete form for untranslatable entities.
   */
  public function testUntranslatableEntities() {
    $selection = [];

    $entity1 = EntityTestRev::create(['type' => 'default', 'name' => 'entity1']);
    $entity1->save();
    $selection[$entity1->id()]['en'] = 'en';

    $entity2 = EntityTestRev::create(['type' => 'default', 'name' => 'entity2']);
    $entity2->save();
    $selection[$entity2->id()]['en'] = 'en';

    // This entity will be inaccessible because of
    // Drupal\entity_test\EntityTestAccessControlHandler.
    $entity3 = EntityTestRev::create(['type' => 'default', 'name' => 'forbid_access']);
    $entity3->save();
    $selection[$entity3->id()]['en'] = 'en';

    // This entity will be inaccessible because of
    // Drupal\entity_test\EntityTestAccessControlHandler.
    $entity4 = EntityTestRev::create(['type' => 'default', 'name' => 'forbid_access']);
    $entity4->save();
    $selection[$entity4->id()]['en'] = 'en';

    // Add the selection to the tempstore just like DeleteAction would.
    $tempstore = \Drupal::service('tempstore.private')->get('entity_delete_multiple_confirm');
    $tempstore->set($this->account->id() . ':entity_test_rev', $selection);

    $this->drupalGet('/entity_test_rev/delete_multiple');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->elementTextContains('css', '.page-title', 'Are you sure you want to delete these test entity - revisions entities?');
    $list_selector = '#entity-test-rev-delete-multiple-confirm-form > div.item-list > ul';
    $assert->elementTextContains('css', $list_selector, 'entity1');
    $assert->elementTextContains('css', $list_selector, 'entity2');
    $delete_button = $this->getSession()->getPage()->findButton('Delete');
    $delete_button->click();
    $assert = $this->assertSession();
    $assert->addressEquals('/user/' . $this->account->id());
    $assert->responseContains('Deleted 2 items.');
    $assert->responseContains('2 items have not been deleted because you do not have the necessary permissions.');

    \Drupal::entityTypeManager()->getStorage('entity_test_mulrevpub')->resetCache();
    $remaining_entities = EntityTestRev::loadMultiple([$entity1->id(), $entity2->id(), $entity3->id(), $entity4->id()]);
    $this->assertCount(2, $remaining_entities);
  }

}
