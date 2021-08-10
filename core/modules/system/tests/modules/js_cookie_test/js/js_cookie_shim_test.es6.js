/**
 * @file
 * Tests adding and removing browser cookies using the jquery_cookie shim.
 */
(({ behaviors }, $) => {
  behaviors.jqueryCookie = {
    attach: () => {
      if (once('js_cookie_test-init', 'body').length) {
        $('.js_cookie_test_add_button').on('click', () => {
          $.cookie('js_cookie_test', 'red panda');
        });
        $('.js_cookie_test_add_raw_button').on('click', () => {
          $.cookie.raw = true;
          $.cookie('js_cookie_test_raw', 'red panda');
        });
        $('.js_cookie_test_add_json_button').on('click', () => {
          $.cookie.json = true;
          $.cookie('js_cookie_test_json', { panda: 'red' });
          $.cookie('js_cookie_test_json_simple', 'red panda');
        });
        $('.js_cookie_test_add_json_string_button').on('click', () => {
          $.cookie.json = false;
          $.cookie('js_cookie_test_json_string', { panda: 'red' });
        });
        $('.js_cookie_test_remove_button').on('click', () => {
          $.removeCookie('js_cookie_test');
        });
      }
    },
  };
})(Drupal, jQuery);
