<?php

namespace Drupal\Tests\rdf\Functional\Jsonapi;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Tests\jsonapi\Functional\JsonApiFunctionalTestBase;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API regression tests.
 *
 * @group jsonapi
 * @group rdf
 */
class JsonApiRegressionTest extends JsonApiFunctionalTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
    'comment',
    'rdf',
  ];

  /**
   * Ensure that child comments can be retrieved via JSON:API.
   */
  public function testLeakedCacheMetadataViaRdfFromIssue3053827(): void {
    $this->addDefaultCommentField('node', 'article');
    $this->rebuildAll();

    Node::create([
      'title' => 'Commented Node',
      'type' => 'article',
    ])->save();
    $default_values = [
      'entity_id' => 1,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => 1,
    ];
    $parent = Comment::create(['subject' => 'Marlin'] + $default_values);
    $parent->save();
    $child = Comment::create(['subject' => 'Nemo', 'pid' => $parent->id()] + $default_values);
    $child->save();

    $user = $this->drupalCreateUser(['access comments']);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ];

    // Requesting the comment collection should succeed.
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/comment/comment'), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

}
