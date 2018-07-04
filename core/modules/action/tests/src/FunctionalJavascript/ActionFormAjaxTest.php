<?php

namespace Drupal\Tests\action\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests action plugins using Javascript.
 *
 * @group action
 */
class ActionFormAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['action', 'action_form_ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);
  }

  /**
   * Tests action plugins with AJAX save their configuration.
   */
  public function testActionConfigurationWithAjax() {
    $url = Url::fromRoute('action.admin_add', ['action_id' => 'action_form_ajax_test']);
    $this->drupalGet($url);
    $page = $this->getSession()->getPage();

    $id = 'test_plugin';
    $this->assertSession()->waitForElementVisible('named', ['button', 'Edit'])->press();
    $this->assertSession()->waitForElementVisible('css', '[name="id"]')->setValue($id);

    $page->find('css', '[name="having_a_party"]')
      ->check();
    $this->assertSession()->waitForElementVisible('css', '[name="party_time"]');

    $party_time = 'Evening';
    $page->find('css', '[name="party_time"]')
      ->setValue($party_time);

    $page->find('css', '[value="Save"]')
      ->click();

    $url = Url::fromRoute('entity.action.collection');
    $this->assertSession()->pageTextContains('The action has been successfully saved.');
    $this->assertSession()->addressEquals($url);

    // Check storage.
    $instance = Action::load($id);
    $configuration = $instance->getPlugin()->getConfiguration();
    $this->assertEquals(['party_time' => $party_time], $configuration);

    // Configuration should be shown in edit form.
    $this->drupalGet($instance->toUrl('edit-form'));
    $this->assertSession()->checkboxChecked('having_a_party');
    $this->assertSession()->fieldValueEquals('party_time', $party_time);
  }

}
