<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the usage of form caching for AJAX forms.
 *
 * @group Ajax
 */
class AjaxFormCacheTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_test', 'ajax_forms_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the usage of form cache for AJAX forms.
   */
  public function testFormCacheUsage() {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable */
    $key_value_expirable = \Drupal::service('keyvalue.expirable')->get('form');
    $this->drupalLogin($this->rootUser);

    // Ensure that the cache is empty.
    $this->assertCount(0, $key_value_expirable->getAll());

    // Visit an AJAX form that is not cached, 3 times.
    $uncached_form_url = Url::fromRoute('ajax_forms_test.commands_form');
    $this->drupalGet($uncached_form_url);
    $this->drupalGet($uncached_form_url);
    $this->drupalGet($uncached_form_url);

    // The number of cache entries should not have changed.
    $this->assertCount(0, $key_value_expirable->getAll());
  }

  /**
   * Tests AJAX forms in blocks.
   */
  public function testBlockForms() {
    $this->container->get('module_installer')->install(['block', 'search']);
    $this->rebuildContainer();
    $this->container->get('router.builder')->rebuild();
    $this->drupalLogin($this->rootUser);

    $this->drupalPlaceBlock('search_form_block', ['weight' => -5]);
    $this->drupalPlaceBlock('ajax_forms_test_block');

    $this->drupalGet('');
    $session = $this->getSession();

    // Select first option and trigger ajax update.
    $session->getPage()->selectFieldOption('edit-test1', 'option1');

    // DOM update: The InsertCommand in the AJAX response changes the text
    // in the option element to 'Option1!!!'.
    $opt1_selector = $this->assertSession()->waitForElement('css', "select[data-drupal-selector='edit-test1'] option:contains('Option 1!!!')");
    $this->assertNotEmpty($opt1_selector);
    $this->assertTrue($opt1_selector->isSelected());

    // Confirm option 3 exists.
    $page = $session->getPage();
    $opt3_selector = $page->find('xpath', '//select[@data-drupal-selector="edit-test1"]//option[@value="option3"]');
    $this->assertNotEmpty($opt3_selector);

    // Confirm success message appears after a submit.
    $page->findButton('edit-submit')->click();
    $this->assertSession()->waitForButton('edit-submit');
    $updated_page = $session->getPage();
    $updated_page->hasContent('Submission successful.');
  }

  /**
   * Tests AJAX forms on pages with a query string.
   */
  public function testQueryString() {
    $this->container->get('module_installer')->install(['block']);
    $this->drupalLogin($this->rootUser);

    $this->drupalPlaceBlock('ajax_forms_test_block');

    $url = Url::fromRoute('entity.user.canonical', ['user' => $this->rootUser->id()], ['query' => ['foo' => 'bar']]);
    $this->drupalGet($url);

    $session = $this->getSession();
    // Select first option and trigger ajax update.
    $session->getPage()->selectFieldOption('edit-test1', 'option1');

    // DOM update: The InsertCommand in the AJAX response changes the text
    // in the option element to 'Option1!!!'.
    $opt1_selector = $this->assertSession()->waitForElement('css', "option:contains('Option 1!!!')");
    $this->assertNotEmpty($opt1_selector);

    $url->setOption('query', [
      'foo' => 'bar',
      FormBuilderInterface::AJAX_FORM_REQUEST => 1,
      MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
    ]);
    $this->assertUrl($url);
  }

}
