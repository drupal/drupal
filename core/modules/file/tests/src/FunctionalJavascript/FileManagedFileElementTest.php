<?php

namespace Drupal\Tests\file\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the 'managed_file' element type.
 *
 * @group file
 */
class FileManagedFileElementTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'file_module_test', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['access content', 'access administration pages', 'administer site configuration', 'administer users', 'administer permissions', 'administer content types', 'administer node fields', 'administer node display', 'administer nodes', 'bypass node access']);
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests the managed_file element type.
   */
  public function testManagedFile() {
    // Perform the tests with all permutations of $form['#tree'],
    // $element['#extended'], and $element['#multiple'].
    $filename = \Drupal::service('file_system')->tempnam('temporary://', "testManagedFile") . '.txt';
    file_put_contents($filename, $this->randomString(128));
    foreach ([0, 1] as $tree) {
      foreach ([0, 1] as $extended) {
        foreach ([0, 1] as $multiple) {
          $path = 'file/test/' . $tree . '/' . $extended . '/' . $multiple;
          $input_base_name = $tree ? 'nested_file' : 'file';
          $file_field_name = $multiple ? 'files[' . $input_base_name . '][]' : 'files[' . $input_base_name . ']';

          // Now, test the Upload and Remove buttons, with Ajax.
          // Upload, then Submit.
          $last_fid_prior = $this->getLastFileId();
          $this->drupalGet($path);
          $this->getSession()->getPage()->attachFileToField($file_field_name, $this->container->get('file_system')->realpath($filename));
          $uploaded_file = $this->assertSession()->waitForElement('css', '.file--mime-text-plain');
          $this->assertNotEmpty($uploaded_file);
          $last_fid = $this->getLastFileId();
          $this->assertGreaterThan($last_fid_prior, $last_fid, 'New file got uploaded.');
          $this->drupalPostForm(NULL, [], t('Save'));

          // Remove, then Submit.
          $remove_button_title = $multiple ? t('Remove selected') : t('Remove');
          $this->drupalGet($path . '/' . $last_fid);
          if ($multiple) {
            $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $last_fid . '][selected]';
            $this->getSession()->getPage()->checkField($selected_checkbox);
          }
          $this->getSession()->getPage()->pressButton($remove_button_title);
          $this->assertSession()->assertWaitOnAjaxRequest();
          $this->drupalPostForm(NULL, [], t('Save'));
          $this->assertSession()->responseContains(t('The file ids are %fids.', ['%fids' => '']));

          // Upload, then Remove, then Submit.
          $this->drupalGet($path);
          $this->getSession()->getPage()->attachFileToField($file_field_name, $this->container->get('file_system')->realpath($filename));
          $uploaded_file = $this->assertSession()->waitForElement('css', '.file--mime-text-plain');
          $this->assertNotEmpty($uploaded_file);
          if ($multiple) {
            $selected_checkbox = ($tree ? 'nested[file]' : 'file') . '[file_' . $this->getLastFileId() . '][selected]';
            $this->getSession()->getPage()->checkField($selected_checkbox);
          }
          $this->getSession()->getPage()->pressButton($remove_button_title);
          $this->assertSession()->assertWaitOnAjaxRequest();

          $this->drupalPostForm(NULL, [], t('Save'));
          $this->assertSession()->responseContains(t('The file ids are %fids.', ['%fids' => '']));
        }
      }
    }
  }

  /**
   * Retrieves the fid of the last inserted file.
   */
  protected function getLastFileId() {
    return (int) \Drupal::entityQueryAggregate('file')->aggregate('fid', 'max')->execute()[0]['fid_max'];
  }

}
