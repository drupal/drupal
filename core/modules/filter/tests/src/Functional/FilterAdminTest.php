<?php

namespace Drupal\Tests\filter\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Thoroughly test the administrative interface of the filter module.
 *
 * @group filter
 */
class FilterAdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'filter',
    'node',
    'filter_test_plugin',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with permissions to create pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Set up the filter formats used by this test.
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em>',
          ],
        ],
      ],
    ]);
    $basic_html_format->save();
    $restricted_html_format = FilterFormat::create([
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => -10,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em> <h4>',
          ],
        ],
        'filter_autop' => [
          'status' => TRUE,
          'weight' => 0,
        ],
        'filter_url' => [
          'status' => TRUE,
          'weight' => 0,
        ],
        'filter_htmlcorrector' => [
          'status' => TRUE,
          'weight' => 10,
        ],
      ],
    ]);
    $restricted_html_format->save();
    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ]);
    $full_html_format->save();

    $this->adminUser = $this->drupalCreateUser([
      'administer filters',
      $basic_html_format->getPermissionName(),
      $restricted_html_format->getPermissionName(),
      $full_html_format->getPermissionName(),
      'access site reports',
    ]);

    $this->webUser = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
    user_role_grant_permissions('authenticated', [$basic_html_format->getPermissionName()]);
    user_role_grant_permissions('anonymous', [$restricted_html_format->getPermissionName()]);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests the format administration functionality.
   */
  public function testFormatAdmin() {
    // Add text format.
    $this->drupalGet('admin/config/content/formats');
    $this->clickLink('Add text format');
    $format_id = mb_strtolower($this->randomMachineName());
    $name = $this->randomMachineName();
    $edit = [
      'format' => $format_id,
      'name' => $name,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Verify default weight of the text format.
    $this->drupalGet('admin/config/content/formats');
    $this->assertSession()->fieldValueEquals("formats[$format_id][weight]", 0);

    // Change the weight of the text format.
    $edit = [
      "formats[$format_id][weight]" => 5,
    ];
    $this->drupalGet('admin/config/content/formats');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->fieldValueEquals("formats[$format_id][weight]", 5);

    // Edit text format.
    $this->drupalGet('admin/config/content/formats');
    $destination = Url::fromRoute('filter.admin_overview')->toString();
    $edit_href = Url::fromRoute('entity.filter_format.edit_form', ['filter_format' => $format_id], ['query' => ['destination' => $destination]])->toString();
    $this->assertSession()->linkByHrefExists($edit_href);
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id);
    $this->submitForm([], 'Save configuration');

    // Verify that the custom weight of the text format has been retained.
    $this->drupalGet('admin/config/content/formats');
    $this->assertSession()->fieldValueEquals("formats[$format_id][weight]", 5);

    // Disable text format.
    $this->assertSession()->linkByHrefExists('admin/config/content/formats/manage/' . $format_id . '/disable');
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id . '/disable');
    $this->submitForm([], 'Disable');

    // Verify that disabled text format no longer exists.
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id);
    $this->assertSession()->statusCodeEquals(404);

    // Attempt to create a format of the same machine name as the disabled
    // format but with a different human readable name.
    $edit = [
      'format' => $format_id,
      'name' => 'New format',
    ];
    $this->drupalGet('admin/config/content/formats/add');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Attempt to create a format of the same human readable name as the
    // disabled format but with a different machine name.
    $edit = [
      'format' => 'new_format',
      'name' => $name,
    ];
    $this->drupalGet('admin/config/content/formats/add');
    $this->submitForm($edit, 'Save configuration');
    $this->assertRaw(t('Text format names must be unique. A format named %name already exists.', [
      '%name' => $name,
    ]));
  }

  /**
   * Tests filter administration functionality.
   */
  public function testFilterAdmin() {
    $first_filter = 'filter_autop';
    $second_filter = 'filter_url';

    $basic = 'basic_html';
    $restricted = 'restricted_html';
    $full = 'full_html';
    $plain = 'plain_text';

    // Check that the fallback format exists and cannot be disabled.
    $this->assertSame($plain, filter_fallback_format(), 'The fallback format is set to plain text.');
    $this->drupalGet('admin/config/content/formats');
    $this->assertNoRaw('admin/config/content/formats/manage/' . $plain . '/disable');
    $this->drupalGet('admin/config/content/formats/manage/' . $plain . '/disable');
    $this->assertSession()->statusCodeEquals(403);

    // Verify access permissions to Full HTML format.
    $full_format = FilterFormat::load($full);
    $this->assertTrue($full_format->access('use', $this->adminUser), 'Admin user may use Full HTML.');
    $this->assertFalse($full_format->access('use', $this->webUser), 'Web user may not use Full HTML.');

    // Add an additional tag and extra spaces and returns.
    $edit = [];
    $edit['filters[filter_html][settings][allowed_html]'] = "<a>   <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>\r\n<quote>";
    $this->drupalGet('admin/config/content/formats/manage/' . $restricted);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $restricted);
    $this->drupalGet('admin/config/content/formats/manage/' . $restricted);
    // Check that the allowed HTML tag was added and the string reformatted.
    $this->assertSession()->fieldValueEquals('filters[filter_html][settings][allowed_html]', "<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd> <quote>");

    $elements = $this->xpath('//select[@name=:first]/following::select[@name=:second]', [
      ':first' => 'filters[' . $first_filter . '][weight]',
      ':second' => 'filters[' . $second_filter . '][weight]',
    ]);
    $this->assertNotEmpty($elements, 'Order confirmed in admin interface.');

    // Reorder filters.
    $edit = [];
    $edit['filters[' . $second_filter . '][weight]'] = 1;
    $edit['filters[' . $first_filter . '][weight]'] = 2;
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $restricted);
    $this->drupalGet('admin/config/content/formats/manage/' . $restricted);
    $this->assertSession()->fieldValueEquals('filters[' . $second_filter . '][weight]', 1);
    $this->assertSession()->fieldValueEquals('filters[' . $first_filter . '][weight]', 2);

    $elements = $this->xpath('//select[@name=:first]/following::select[@name=:second]', [
      ':first' => 'filters[' . $second_filter . '][weight]',
      ':second' => 'filters[' . $first_filter . '][weight]',
    ]);
    $this->assertNotEmpty($elements, 'Reorder confirmed in admin interface.');

    $filter_format = FilterFormat::load($restricted);
    foreach ($filter_format->filters() as $filter_name => $filter) {
      if ($filter_name == $second_filter || $filter_name == $first_filter) {
        $filters[] = $filter_name;
      }
    }
    // Ensure that the second filter is now before the first filter.
    $this->assertEquals($filter_format->filters($second_filter)->weight + 1, $filter_format->filters($first_filter)->weight, 'Order confirmed in configuration.');

    // Add format.
    $edit = [];
    $edit['format'] = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['roles[' . RoleInterface::AUTHENTICATED_ID . ']'] = 1;
    $edit['filters[' . $second_filter . '][status]'] = TRUE;
    $edit['filters[' . $first_filter . '][status]'] = TRUE;
    $this->drupalGet('admin/config/content/formats/add');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats');
    $this->assertRaw(t('Added text format %format.', ['%format' => $edit['name']]));

    filter_formats_reset();
    $format = FilterFormat::load($edit['format']);
    $this->assertNotNull($format, 'Format found in database.');
    $this->drupalGet('admin/config/content/formats/manage/' . $format->id());
    $this->assertSession()->checkboxChecked('roles[' . RoleInterface::AUTHENTICATED_ID . ']');
    $this->assertSession()->checkboxChecked('filters[' . $second_filter . '][status]');
    $this->assertSession()->checkboxChecked('filters[' . $first_filter . '][status]');

    // Disable new filter.
    $this->drupalGet('admin/config/content/formats/manage/' . $format->id() . '/disable');
    $this->submitForm([], 'Disable');
    $this->assertSession()->addressEquals('admin/config/content/formats');
    $this->assertRaw(t('Disabled text format %format.', ['%format' => $edit['name']]));

    // Allow authenticated users on full HTML.
    $format = FilterFormat::load($full);
    $edit = [];
    $edit['roles[' . RoleInterface::ANONYMOUS_ID . ']'] = 0;
    $edit['roles[' . RoleInterface::AUTHENTICATED_ID . ']'] = 1;
    $this->drupalGet('admin/config/content/formats/manage/' . $full);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $full);
    $this->assertRaw(t('The text format %format has been updated.', ['%format' => $format->label()]));

    // Switch user.
    $this->drupalLogin($this->webUser);

    $this->drupalGet('node/add/page');
    $this->assertRaw('<option value="' . $full . '">Full HTML</option>');

    // Use basic HTML and see if it removes tags that are not allowed.
    $body = '<em>' . $this->randomMachineName() . '</em>';
    $extra_text = 'text';
    $text = $body . '<random>' . $extra_text . '</random>';

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $text;
    $edit['body[0][format]'] = $basic;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Basic page ' . $edit['title[0][value]'] . ' has been created.');

    // Verify that the creation message contains a link to a node.
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "messages")]//a[contains(@href, "node/")]');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node, 'Node found in database.');

    $this->drupalGet('node/' . $node->id());
    // Check that filter removed invalid tag.
    $this->assertRaw($body . $extra_text);

    // Use plain text and see if it escapes all tags, whether allowed or not.
    // In order to test plain text, we have to enable the hidden variable for
    // "show_fallback_format", which displays plain text in the format list.
    $this->config('filter.settings')
      ->set('always_show_fallback_choice', TRUE)
      ->save();
    $edit = [];
    $edit['body[0][format]'] = $plain;
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->assertEscaped($text);
    $this->config('filter.settings')
      ->set('always_show_fallback_choice', FALSE)
      ->save();

    // Switch user.
    $this->drupalLogin($this->adminUser);

    // Clean up.
    // Allowed tags.
    $edit = [];
    $edit['filters[filter_html][settings][allowed_html]'] = '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>';
    $this->drupalGet('admin/config/content/formats/manage/' . $basic);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $basic);
    $this->drupalGet('admin/config/content/formats/manage/' . $basic);
    $this->assertSession()->fieldValueEquals('filters[filter_html][settings][allowed_html]', $edit['filters[filter_html][settings][allowed_html]']);

    // Full HTML.
    $edit = [];
    $edit['roles[' . RoleInterface::AUTHENTICATED_ID . ']'] = FALSE;
    $this->drupalGet('admin/config/content/formats/manage/' . $full);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $full);
    $this->assertRaw(t('The text format %format has been updated.', ['%format' => $format->label()]));
    $this->drupalGet('admin/config/content/formats/manage/' . $full);
    $this->assertSession()->fieldValueEquals('roles[' . RoleInterface::AUTHENTICATED_ID . ']', $edit['roles[' . RoleInterface::AUTHENTICATED_ID . ']']);

    // Filter order.
    $edit = [];
    $edit['filters[' . $second_filter . '][weight]'] = 2;
    $edit['filters[' . $first_filter . '][weight]'] = 1;
    $this->drupalGet('admin/config/content/formats/manage/' . $basic);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->addressEquals('admin/config/content/formats/manage/' . $basic);
    $this->drupalGet('admin/config/content/formats/manage/' . $basic);
    $this->assertSession()->fieldValueEquals('filters[' . $second_filter . '][weight]', $edit['filters[' . $second_filter . '][weight]']);
    $this->assertSession()->fieldValueEquals('filters[' . $first_filter . '][weight]', $edit['filters[' . $first_filter . '][weight]']);
  }

  /**
   * Tests the URL filter settings form is properly validated.
   */
  public function testUrlFilterAdmin() {
    // The form does not save with an invalid filter URL length.
    $edit = [
      'filters[filter_url][settings][filter_url_length]' => $this->randomMachineName(4),
    ];
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $this->submitForm($edit, 'Save configuration');
    $this->assertNoRaw(t('The text format %format has been updated.', ['%format' => 'Basic HTML']));
  }

  /**
   * Tests whether filter tips page is not HTML escaped.
   */
  public function testFilterTipHtmlEscape() {
    $this->drupalLogin($this->adminUser);
    global $base_url;

    $site_name_with_markup = 'Filter test <script>alert(\'here\');</script> site name';
    $this->config('system.site')->set('name', $site_name_with_markup)->save();

    // It is not possible to test the whole filter tip page.
    // Therefore we test only some parts.
    $link = '<a href="' . $base_url . '">' . Html::escape($site_name_with_markup) . '</a>';
    $ampersand = '&amp;';
    $link_as_code = '<code>' . Html::escape($link) . '</code>';
    $ampersand_as_code = '<code>' . Html::escape($ampersand) . '</code>';

    $this->drupalGet('filter/tips');

    $this->assertRaw('<td class="type">' . $link_as_code . '</td>');
    $this->assertRaw('<td class="get">' . $link . '</td>');
    $this->assertRaw('<td class="type">' . $ampersand_as_code . '</td>');
    $this->assertRaw('<td class="get">' . $ampersand . '</td>');
  }

  /**
   * Tests whether a field using a disabled format is rendered.
   */
  public function testDisabledFormat() {
    // Create a node type and add a standard body field.
    $node_type = NodeType::create(['type' => mb_strtolower($this->randomMachineName())]);
    $node_type->save();
    node_add_body_field($node_type, $this->randomString());

    // Create a text format with a filter that returns a static string.
    $format = FilterFormat::create([
      'name' => $this->randomString(),
      'format' => $format_id = mb_strtolower($this->randomMachineName()),
    ]);
    $format->setFilterConfig('filter_static_text', ['status' => TRUE]);
    $format->save();

    // Create a new node of the new node type.
    $node = Node::create([
      'type' => $node_type->id(),
      'title' => $this->randomString(),
    ]);
    $body_value = $this->randomString();
    $node->body->value = $body_value;
    $node->body->format = $format_id;
    $node->save();

    // The format is used and we should see the static text instead of the body
    // value.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('filtered text');

    // Disable the format.
    $format->disable()->save();

    $this->drupalGet($node->toUrl());

    // The format is not used anymore.
    $this->assertNoText('filtered text');
    // The text is not displayed unfiltered or escaped.
    $this->assertNoRaw($body_value);
    $this->assertSession()->assertNoEscaped($body_value);

    // Visit the dblog report page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/dblog');
    // The correct message has been logged.
    $this->assertRaw(sprintf('Disabled text format: %s.', $format_id));

    // Programmatically change the text format to something random so we trigger
    // the missing text format message.
    $format_id = $this->randomMachineName();
    $node->body->format = $format_id;
    $node->save();
    $this->drupalGet($node->toUrl());
    // The text is not displayed unfiltered or escaped.
    $this->assertNoRaw($body_value);
    $this->assertSession()->assertNoEscaped($body_value);

    // Visit the dblog report page.
    $this->drupalGet('admin/reports/dblog');
    // The missing text format message has been logged.
    $this->assertRaw(sprintf('Missing text format: %s.', $format_id));
  }

}
