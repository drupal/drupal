<?php

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\EntityViewTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests summary length.
 *
 * @group node
 */
class SummaryLengthTest extends KernelTestBase {

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use EntityViewTrait {
    buildEntityView as drupalBuildEntityView;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'datetime',
    'user',
    'system',
    'filter',
    'field',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('date_format');
    $this->installConfig('filter');
    $this->installConfig('node');

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    DateFormat::create([
      'id' => 'fallback',
      'label' => 'Fallback',
      'pattern' => 'Y-m-d',
    ])->save();
  }

  /**
   * Tests the node summary length functionality.
   */
  public function testSummaryLength() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create a node to view.
    $settings = [
      'body' => [['value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam vitae arcu at leo cursus laoreet. Curabitur dui tortor, adipiscing malesuada tempor in, bibendum ac diam. Cras non tellus a libero pellentesque condimentum. What is a Drupalism? Suspendisse ac lacus libero. Ut non est vel nisl faucibus interdum nec sed leo. Pellentesque sem risus, vulputate eu semper eget, auctor in libero. Ut fermentum est vitae metus convallis scelerisque. Phasellus pellentesque rhoncus tellus, eu dignissim purus posuere id. Quisque eu fringilla ligula. Morbi ullamcorper, lorem et mattis egestas, tortor neque pretium velit, eget eleifend odio turpis eu purus. Donec vitae metus quis leo pretium tincidunt a pulvinar sem. Morbi adipiscing laoreet mauris vel placerat. Nullam elementum, nisl sit amet scelerisque malesuada, dolor nunc hendrerit quam, eu ultrices erat est in orci. Curabitur feugiat egestas nisl sed accumsan.']],
      'promote' => 1,
    ];
    $node = $this->drupalCreateNode($settings);
    $this->assertNotEmpty(Node::load($node->id()), 'Node created.');

    // Render the node as a teaser.
    $content = $this->drupalBuildEntityView($node, 'teaser');
    $this->assertTrue(strlen($content['body'][0]['#markup']) < 600, 'Teaser is less than 600 characters long.');
    $this->setRawContent($renderer->renderRoot($content));
    // The string 'What is a Drupalism?' is between the 200th and 600th
    // characters of the node body, so it should be included if the summary is
    // 600 characters long.
    $expected = 'What is a Drupalism?';
    $this->assertRaw($expected);

    // Change the teaser length for "Basic page" content type.
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', $node->getType(), 'teaser');
    $display_options = $display->getComponent('body');
    $display_options['settings']['trim_length'] = 200;
    $display->setComponent('body', $display_options)
      ->save();

    // Render the node as a teaser again and check that the summary is now only
    // 200 characters in length and so does not include 'What is a Drupalism?'.
    $content = $this->drupalBuildEntityView($node, 'teaser');
    $this->assertTrue(strlen($content['body'][0]['#markup']) < 200, 'Teaser is less than 200 characters long.');
    $this->setRawContent($renderer->renderRoot($content));
    $this->assertText($node->label());
    $this->assertNoRaw($expected);
  }

}
