<?php

namespace Drupal\Tests\quickedit\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests using a custom pipeline with Quick Edit.
 *
 * @group quickedit
 */
class QuickEditCustomPipelineTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'quickedit',
    'quickedit_test',
    'node',
  ];

  /**
   * Tests that Quick Edit works with custom render pipelines.
   */
  public function testCustomPipeline() {
    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node = $this->createNode(['type' => 'article']);
    $editor_user = $this->drupalCreateUser([
      'access content',
      'create article content',
      'edit any article content',
      'access in-place editing',
    ]);
    $this->drupalLogin($editor_user);

    $custom_render_url = $this->buildUrl('quickedit/form/node/' . $node->id() . '/body/en/quickedit_test-custom-render-data');

    $client = $this->getHttpClient();
    $post = ['nocssjs' => 'true'];
    $response = $client->post($custom_render_url, [
      'body' => http_build_query($post),
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax'],
      'cookies' => $this->getSessionCookies(),
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);

    $this->assertEquals(200, $response->getStatusCode());

    $ajax_commands = Json::decode($response->getBody());
    // Request editing to render results with the custom render pipeline.

    // Prepare form values for submission. drupalPostAJAX() is not suitable for
    // handling pages with JSON responses, so we need our own solution here.
    $form_tokens_found = preg_match('/\sname="form_token" value="([^"]+)"/', $ajax_commands[0]['data'], $token_match) && preg_match('/\sname="form_build_id" value="([^"]+)"/', $ajax_commands[0]['data'], $build_id_match);
    $this->assertTrue($form_tokens_found, 'Form tokens found in output.');

    $post = [
      'form_id' => 'quickedit_field_form',
      'form_token' => $token_match[1],
      'form_build_id' => $build_id_match[1],
      'body[0][summary]' => '',
      'body[0][value]' => '<p>Fine thanks.</p>',
      'body[0][format]' => 'filtered_html',
      'op' => t('Save'),
    ];
    // Assume there is another field on this page, which doesn't use a custom
    // render pipeline, but the default one, and it uses the "full" view mode.
    $post += ['other_view_modes[]' => 'full'];

    // Submit field form and check response. Should render with the custom
    // render pipeline.
    $response = $client->post($custom_render_url, [
      'body' => http_build_query($post),
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax'],
      'cookies' => $this->getSessionCookies(),
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ]);
    $ajax_commands = Json::decode($response->getBody());
    $this->assertIdentical(1, count($ajax_commands), 'The field form HTTP request results in one AJAX command.');
    $this->assertIdentical('quickeditFieldFormSaved', $ajax_commands[0]['command'], 'The first AJAX command is a quickeditFieldFormSaved command.');
    $this->assertTrue(strpos($ajax_commands[0]['data'], 'Fine thanks.'), 'Form value saved and printed back.');
    $this->assertTrue(strpos($ajax_commands[0]['data'], '<div class="quickedit-test-wrapper">') !== FALSE, 'Custom render pipeline used to render the value.');
    $this->assertIdentical(array_keys($ajax_commands[0]['other_view_modes']), ['full'], 'Field was also rendered in the "full" view mode.');
    $this->assertTrue(strpos($ajax_commands[0]['other_view_modes']['full'], 'Fine thanks.'), '"full" version of field contains the form value.');
  }

}
