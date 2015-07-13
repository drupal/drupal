<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Ajax\AjaxFormCacheTest.
 */

namespace Drupal\system\Tests\Ajax;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Tests the usage of form caching for AJAX forms.
 *
 * @group Ajax
 */
class AjaxFormCacheTest extends AjaxTestBase {

  /**
   * Tests the usage of form cache for AJAX forms.
   */
  public function testFormCacheUsage() {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable */
    $key_value_expirable = \Drupal::service('keyvalue.expirable')->get('form');
    $this->drupalLogin($this->rootUser);

    // Ensure that the cache is empty.
    $this->assertEqual(0, count($key_value_expirable->getAll()));

    // Visit an AJAX form that is not cached, 3 times.
    $uncached_form_url = Url::fromRoute('ajax_forms_test.commands_form');
    $this->drupalGet($uncached_form_url);
    $this->drupalGet($uncached_form_url);
    $this->drupalGet($uncached_form_url);

    // The number of cache entries should not have changed.
    $this->assertEqual(0, count($key_value_expirable->getAll()));
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
    $this->drupalPlaceBlock('ajax_forms_test_block', ['cache' => ['max_age' => 0]]);

    $this->drupalGet('');
    $this->drupalPostAjaxForm(NULL, ['test1' => 'option1'], 'test1');
    $this->assertOptionSelectedWithDrupalSelector('edit-test1', 'option1');
    $this->assertOptionWithDrupalSelector('edit-test1', 'option3');
    $this->drupalPostForm(NULL, ['test1' => 'option1'], 'Submit');
    $this->assertText('Submission successful.');
  }

  /**
   * Tests AJAX forms on pages with a query string.
   */
  public function testQueryString() {
    $this->container->get('module_installer')->install(['block']);
    $this->drupalLogin($this->rootUser);

    $this->drupalPlaceBlock('ajax_forms_test_block', ['cache' => ['max_age' => 0]]);

    $url = Url::fromRoute('entity.user.canonical', ['user' => $this->rootUser->id()], ['query' => ['foo' => 'bar']]);
    $this->drupalGet($url);
    $this->drupalPostAjaxForm(NULL, ['test1' => 'option1'], 'test1');
    $url->setOption('query', [
      'foo' => 'bar',
      FormBuilderInterface::AJAX_FORM_REQUEST => 1,
      MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
    ]);
    $this->assertUrl($url);
  }

}
