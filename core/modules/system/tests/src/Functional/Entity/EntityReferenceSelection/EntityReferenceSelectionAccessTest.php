<?php

namespace Drupal\Tests\system\Functional\Entity\EntityReferenceSelection;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\NodeInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\comment\Entity\Comment;

/**
 * Tests for the base handlers provided by Entity Reference.
 *
 * @group entity_reference
 */
class EntityReferenceSelectionAccessTest extends KernelTestBase {

  use CommentTestTrait;
  use ContentTypeCreationTrait;
  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'field',
    'file',
    'image',
    'node',
    'media',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('file', ['file_usage']);

    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');

    $this->installConfig(['comment', 'field', 'media', 'node', 'taxonomy', 'user']);

    // Create the anonymous and the admin users.
    $anonymous_user = User::create([
      'uid' => 0,
      'name' => '',
    ]);
    $anonymous_user->save();
    $admin_user = User::create([
      'uid' => 1,
      'name' => 'admin',
      'status' => 1,
    ]);
    $admin_user->save();
  }

  /**
   * Checks that a selection plugin returns the expected results.
   *
   * @param array $selection_options
   *   An array of options as required by entity reference selection plugins.
   * @param array $tests
   *   An array of tests to run.
   * @param string $handler_name
   *   The name of the entity type selection handler being tested.
   */
  protected function assertReferenceable(array $selection_options, $tests, $handler_name) {
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($selection_options);

    foreach ($tests as $test) {
      foreach ($test['arguments'] as $arguments) {
        $result = call_user_func_array([$handler, 'getReferenceableEntities'], $arguments);
        $this->assertEqual($result, $test['result'], new FormattableMarkup('Valid result set returned by @handler.', ['@handler' => $handler_name]));

        $result = call_user_func_array([$handler, 'countReferenceableEntities'], $arguments);
        if (!empty($test['result'])) {
          $bundle = key($test['result']);
          $count = count($test['result'][$bundle]);
        }
        else {
          $count = 0;
        }

        $this->assertEqual($result, $count, new FormattableMarkup('Valid count returned by @handler.', ['@handler' => $handler_name]));
      }
    }
  }

  /**
   * Test the node-specific overrides of the entity handler.
   */
  public function testNodeHandler() {
    $selection_options = [
      'target_type' => 'node',
      'handler' => 'default',
      'target_bundles' => NULL,
    ];

    // Build a set of test data.
    // Titles contain HTML-special characters to test escaping.
    $node_values = [
      'published1' => [
        'type' => 'article',
        'status' => NodeInterface::PUBLISHED,
        'title' => 'Node published1 (<&>)',
        'uid' => 1,
      ],
      'published2' => [
        'type' => 'article',
        'status' => NodeInterface::PUBLISHED,
        'title' => 'Node published2 (<&>)',
        'uid' => 1,
      ],
      'unpublished' => [
        'type' => 'article',
        'status' => NodeInterface::NOT_PUBLISHED,
        'title' => 'Node unpublished (<&>)',
        'uid' => 1,
      ],
    ];

    $nodes = [];
    $node_labels = [];
    foreach ($node_values as $key => $values) {
      $node = Node::create($values);
      $node->save();
      $nodes[$key] = $node;
      $node_labels[$key] = Html::escape($node->label());
    }

    // Test as a non-admin.
    $normal_user = $this->createUser(['access content']);
    $this->setCurrentUser($normal_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'article' => [
            $nodes['published1']->id() => $node_labels['published1'],
            $nodes['published2']->id() => $node_labels['published2'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['published1', 'CONTAINS'],
          ['Published1', 'CONTAINS'],
        ],
        'result' => [
          'article' => [
            $nodes['published1']->id() => $node_labels['published1'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['published2', 'CONTAINS'],
          ['Published2', 'CONTAINS'],
        ],
        'result' => [
          'article' => [
            $nodes['published2']->id() => $node_labels['published2'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['invalid node', 'CONTAINS'],
        ],
        'result' => [],
      ],
      [
        'arguments' => [
          ['Node unpublished', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Node handler');

    // Test as an admin.
    $content_admin = $this->createUser(['access content', 'bypass node access']);
    $this->setCurrentUser($content_admin);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'article' => [
            $nodes['published1']->id() => $node_labels['published1'],
            $nodes['published2']->id() => $node_labels['published2'],
            $nodes['unpublished']->id() => $node_labels['unpublished'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Node unpublished', 'CONTAINS'],
        ],
        'result' => [
          'article' => [
            $nodes['unpublished']->id() => $node_labels['unpublished'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Node handler (admin)');
  }

  /**
   * Test the user-specific overrides of the entity handler.
   */
  public function testUserHandler() {
    $selection_options = [
      'target_type' => 'user',
      'handler' => 'default',
      'target_bundles' => NULL,
      'include_anonymous' => TRUE,
    ];

    // Build a set of test data.
    $user_values = [
      'anonymous' => User::load(0),
      'admin' => User::load(1),
      'non_admin' => [
        'name' => 'non_admin <&>',
        'mail' => 'non_admin@example.com',
        'roles' => [],
        'pass' => user_password(),
        'status' => 1,
      ],
      'blocked' => [
        'name' => 'blocked <&>',
        'mail' => 'blocked@example.com',
        'roles' => [],
        'pass' => user_password(),
        'status' => 0,
      ],
    ];

    $user_values['anonymous']->name = $this->config('user.settings')->get('anonymous');
    $users = [];

    $user_labels = [];
    foreach ($user_values as $key => $values) {
      if (is_array($values)) {
        $account = User::create($values);
        $account->save();
      }
      else {
        $account = $values;
      }
      $users[$key] = $account;
      $user_labels[$key] = Html::escape($account->getAccountName());
    }

    // Test as a non-admin.
    $this->setCurrentUser($users['non_admin']);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'user' => [
            $users['admin']->id() => $user_labels['admin'],
            $users['non_admin']->id() => $user_labels['non_admin'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['non_admin', 'CONTAINS'],
          ['NON_ADMIN', 'CONTAINS'],
        ],
        'result' => [
          'user' => [
            $users['non_admin']->id() => $user_labels['non_admin'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['invalid user', 'CONTAINS'],
        ],
        'result' => [],
      ],
      [
        'arguments' => [
          ['blocked', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler');

    $this->setCurrentUser($users['admin']);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'user' => [
            $users['anonymous']->id() => $user_labels['anonymous'],
            $users['admin']->id() => $user_labels['admin'],
            $users['non_admin']->id() => $user_labels['non_admin'],
            $users['blocked']->id() => $user_labels['blocked'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['blocked', 'CONTAINS'],
        ],
        'result' => [
          'user' => [
            $users['blocked']->id() => $user_labels['blocked'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Anonymous', 'CONTAINS'],
          ['anonymous', 'CONTAINS'],
        ],
        'result' => [
          'user' => [
            $users['anonymous']->id() => $user_labels['anonymous'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler (admin)');

    // Test the 'include_anonymous' option.
    $selection_options['include_anonymous'] = FALSE;
    $referenceable_tests = [
      [
        'arguments' => [
          ['Anonymous', 'CONTAINS'],
          ['anonymous', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler (does not include anonymous)');

    // Check that the Anonymous user is not included in the results when no
    // label matching is done, for example when using the 'options_select'
    // widget.
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL],
        ],
        'result' => [
          'user' => [
            $users['admin']->id() => $user_labels['admin'],
            $users['non_admin']->id() => $user_labels['non_admin'],
            $users['blocked']->id() => $user_labels['blocked'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'User handler (does not include anonymous)');
  }

  /**
   * Test the comment-specific overrides of the entity handler.
   */
  public function testCommentHandler() {
    $selection_options = [
      'target_type' => 'comment',
      'handler' => 'default',
      'target_bundles' => NULL,
    ];

    // Build a set of test data.
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $node_values = [
      'published' => [
        'type' => 'article',
        'status' => 1,
        'title' => 'Node published',
        'uid' => 1,
      ],
      'unpublished' => [
        'type' => 'article',
        'status' => 0,
        'title' => 'Node unpublished',
        'uid' => 1,
      ],
    ];
    $nodes = [];
    foreach ($node_values as $key => $values) {
      $node = Node::create($values);
      $node->save();
      $nodes[$key] = $node;
    }

    // Create comment field on article.
    $this->addDefaultCommentField('node', 'article');

    $comment_values = [
      'published_published' => [
        'entity_id' => $nodes['published']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::PUBLISHED,
        'subject' => 'Comment Published <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ],
      'published_unpublished' => [
        'entity_id' => $nodes['published']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::NOT_PUBLISHED,
        'subject' => 'Comment Unpublished <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ],
      'unpublished_published' => [
        'entity_id' => $nodes['unpublished']->id(),
        'entity_type' => 'node',
        'field_name' => 'comment',
        'uid' => 1,
        'cid' => NULL,
        'pid' => 0,
        'status' => CommentInterface::NOT_PUBLISHED,
        'subject' => 'Comment Published on Unpublished node <&>',
        'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ],
    ];

    $comments = [];
    $comment_labels = [];
    foreach ($comment_values as $key => $values) {
      $comment = Comment::create($values);
      $comment->save();
      $comments[$key] = $comment;
      $comment_labels[$key] = Html::escape($comment->label());
    }

    // Test as a non-admin.
    $normal_user = $this->createUser(['access content', 'access comments']);
    $this->setCurrentUser($normal_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'comment' => [
            $comments['published_published']->cid->value => $comment_labels['published_published'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Published', 'CONTAINS'],
        ],
        'result' => [
          'comment' => [
            $comments['published_published']->cid->value => $comment_labels['published_published'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['invalid comment', 'CONTAINS'],
        ],
        'result' => [],
      ],
      [
        'arguments' => [
          ['Comment Unpublished', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler');

    // Test as a comment admin.
    $admin_user = $this->createUser(['access content', 'access comments', 'administer comments']);
    $this->setCurrentUser($admin_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'comment' => [
            $comments['published_published']->cid->value => $comment_labels['published_published'],
            $comments['published_unpublished']->cid->value => $comment_labels['published_unpublished'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler (comment admin)');

    // Test as a node and comment admin.
    $admin_user = $this->createUser(['access content', 'access comments', 'administer comments', 'bypass node access']);
    $this->setCurrentUser($admin_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'comment' => [
            $comments['published_published']->cid->value => $comment_labels['published_published'],
            $comments['published_unpublished']->cid->value => $comment_labels['published_unpublished'],
            $comments['unpublished_published']->cid->value => $comment_labels['unpublished_published'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Comment handler (comment + node admin)');
  }

  /**
   * Test the term-specific overrides of the selection handler.
   */
  public function testTermHandler() {
    // Create a 'Tags' vocabulary.
    Vocabulary::create([
      'name' => 'Tags',
      'description' => $this->randomMachineName(),
      'vid' => 'tags',
    ])->save();

    $selection_options = [
      'target_type' => 'taxonomy_term',
      'handler' => 'default',
      'target_bundles' => NULL,
    ];

    // Build a set of test data.
    $term_values = [
      'published1' => [
        'vid' => 'tags',
        'status' => 1,
        'name' => 'Term published1',
      ],
      'published2' => [
        'vid' => 'tags',
        'status' => 1,
        'name' => 'Term published2',
      ],
      'unpublished' => [
        'vid' => 'tags',
        'status' => 0,
        'name' => 'Term unpublished',
      ],
      'published3' => [
        'vid' => 'tags',
        'status' => 1,
        'name' => 'Term published3',
        'parent' => 'unpublished',
      ],
      'published4' => [
        'vid' => 'tags',
        'status' => 1,
        'name' => 'Term published4',
        'parent' => 'published3',
      ],
    ];

    $terms = [];
    $term_labels = [];
    foreach ($term_values as $key => $values) {
      $term = Term::create($values);
      if (isset($values['parent'])) {
        $term->parent->entity = $terms[$values['parent']];
      }
      $term->save();
      $terms[$key] = $term;
      $term_labels[$key] = Html::escape($term->label());
    }

    // Test as a non-admin.
    $normal_user = $this->createUser(['access content']);
    $this->setCurrentUser($normal_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'tags' => [
            $terms['published1']->id() => $term_labels['published1'],
            $terms['published2']->id() => $term_labels['published2'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['published1', 'CONTAINS'],
          ['Published1', 'CONTAINS'],
        ],
        'result' => [
          'tags' => [
            $terms['published1']->id() => $term_labels['published1'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['published2', 'CONTAINS'],
          ['Published2', 'CONTAINS'],
        ],
        'result' => [
          'tags' => [
            $terms['published2']->id() => $term_labels['published2'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['invalid term', 'CONTAINS'],
        ],
        'result' => [],
      ],
      [
        'arguments' => [
          ['Term unpublished', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Term handler');

    // Test as an admin.
    $admin_user = $this->createUser(['access content', 'administer taxonomy']);
    $this->setCurrentUser($admin_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          'tags' => [
            $terms['published1']->id() => $term_labels['published1'],
            $terms['published2']->id() => $term_labels['published2'],
            $terms['unpublished']->id() => $term_labels['unpublished'],
            $terms['published3']->id() => '-' . $term_labels['published3'],
            $terms['published4']->id() => '--' . $term_labels['published4'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Term unpublished', 'CONTAINS'],
        ],
        'result' => [
          'tags' => [
            $terms['unpublished']->id() => $term_labels['unpublished'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Term handler (admin)');
  }

  /**
   * Tests the selection handler for the media entity type.
   */
  public function testMediaHandler() {
    $selection_options = [
      'target_type' => 'media',
      'handler' => 'default',
      'target_bundles' => NULL,
    ];

    // Build a set of test data.
    $media_type = $this->createMediaType('file');
    $media_values = [
      'published' => [
        'bundle' => $media_type->id(),
        'status' => 1,
        'name' => 'Media published',
        'uid' => 1,
      ],
      'unpublished' => [
        'bundle' => $media_type->id(),
        'status' => 0,
        'name' => 'Media unpublished',
        'uid' => 1,
      ],
    ];

    $media_entities = [];
    $media_labels = [];
    foreach ($media_values as $key => $values) {
      $media = Media::create($values);
      $media->save();
      $media_entities[$key] = $media;
      $media_labels[$key] = Html::escape($media->label());
    }

    // Test as a non-admin.
    $normal_user = $this->createUser(['view media']);
    $this->setCurrentUser($normal_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          $media_type->id() => [
            $media_entities['published']->id() => $media_labels['published'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Media unpublished', 'CONTAINS'],
        ],
        'result' => [],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Media handler');

    // Test as an admin.
    $admin_user = $this->createUser(['view media', 'administer media']);
    $this->setCurrentUser($admin_user);
    $referenceable_tests = [
      [
        'arguments' => [
          [NULL, 'CONTAINS'],
        ],
        'result' => [
          $media_type->id() => [
            $media_entities['published']->id() => $media_labels['published'],
            $media_entities['unpublished']->id() => $media_labels['unpublished'],
          ],
        ],
      ],
      [
        'arguments' => [
          ['Media unpublished', 'CONTAINS'],
        ],
        'result' => [
          $media_type->id() => [
            $media_entities['unpublished']->id() => $media_labels['unpublished'],
          ],
        ],
      ],
    ];
    $this->assertReferenceable($selection_options, $referenceable_tests, 'Media handler (admin)');
  }

}
