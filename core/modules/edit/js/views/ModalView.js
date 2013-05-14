/**
 * @file
 * A Backbone View that provides an interactive modal.
 */
(function ($, Backbone, Drupal) {

"use strict";

Drupal.edit.ModalView = Backbone.View.extend({

  message: null,
  buttons: null,
  callback: null,
  $elementsToHide: null,

  events: {
    'click button': 'onButtonClick'
  },

  /**
   * {@inheritdoc}
   *
   * @param Object options
   *   An object with the following keys:
   *   - String message: a message to show in the modal.
   *   - Array buttons: a set of buttons with 'action's defined, ready to be
         passed to Drupal.theme.editButtons().
   *   - Function callback: a callback that will receive the 'action' of the
   *     clicked button.
   *
   * @see Drupal.theme.editModal()
   * @see Drupal.theme.editButtons()
   */
  initialize: function (options) {
    this.message = options.message;
    this.buttons = options.buttons;
    this.callback = options.callback;
  },

  /**
   * {@inheritdoc}
   */
  render: function () {
    this.setElement(Drupal.theme('editModal', {}));
    this.$el.appendTo('body');
    // Template.
    this.$('.main p').text(this.message);
    var $actions = $(Drupal.theme('editButtons', { 'buttons' : this.buttons}));
    this.$('.actions').append($actions);

    // Show the modal with an animation.
    var that = this;
    setTimeout(function () {
      that.$el.removeClass('edit-animate-invisible');
    }, 0);
  },

  /**
   * Passes the clicked button action to the callback; closes the modal.
   *
   * @param jQuery event
   */
  onButtonClick: function (event) {
    event.stopPropagation();
    event.preventDefault();

    // Remove after animation.
    var that = this;
    this.$el
      .addClass('edit-animate-invisible')
      .on(Drupal.edit.util.constants.transitionEnd, function (e) {
        that.remove();
      });

    var action = $(event.target).attr('data-edit-modal-action');
    return this.callback(action);
  }
});

})(jQuery, Backbone, Drupal);
