/**
 * @file
 * Drupal behavior to attach click event handlers to ajax-insert and
 * ajax-insert-inline links for testing ajax requests.
 */

(function ($, window, Drupal) {
  Drupal.behaviors.insertTest = {
    attach(context) {
      $('.ajax-insert')
        .once('ajax-insert')
        .on('click', (event) => {
          event.preventDefault();
          const ajaxSettings = {
            url: event.currentTarget.getAttribute('href'),
            wrapper: 'ajax-target',
            base: false,
            element: false,
            method: event.currentTarget.getAttribute('data-method'),
            effect: event.currentTarget.getAttribute('data-effect'),
          };
          const myAjaxObject = Drupal.ajax(ajaxSettings);
          myAjaxObject.execute();
        });

      $('.ajax-insert-inline')
        .once('ajax-insert')
        .on('click', (event) => {
          event.preventDefault();
          const ajaxSettings = {
            url: event.currentTarget.getAttribute('href'),
            wrapper: 'ajax-target-inline',
            base: false,
            element: false,
            method: event.currentTarget.getAttribute('data-method'),
            effect: event.currentTarget.getAttribute('data-effect'),
          };
          const myAjaxObject = Drupal.ajax(ajaxSettings);
          myAjaxObject.execute();
        });

      $(context).addClass('processed');
    },
  };
})(jQuery, window, Drupal);
