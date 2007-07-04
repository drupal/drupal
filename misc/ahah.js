// $Id: ahah.js,v 1.1 2007/07/04 15:42:38 dries Exp $

/**
 * Provides AJAX-like page updating via AHAH (Asynchronous HTML and HTTP).
 *
 * AHAH is a method of making a request via Javascript while viewing an HTML
 * page. The request returns a small chunk of HTML, which is then directly
 * injected into the page.
 *
 * Drupal uses this file to enhance form elements with #ahah_path and
 * #ahah_wrapper properties. If set, this file will automatically be included
 * to provide AHAH capabilities.
 */

/**
 * Attaches the ahah behaviour to each ahah form element.
 */
Drupal.behaviors.ahah = function(context) {
  for (var base in Drupal.settings.ahah) {
    if (!$('#'+ base + '.ahah-processed').size()) {
      var element = Drupal.settings.ahah[base];
      var ahah = new Drupal.ahah(base, element);
      $('#'+ base).addClass('ahah-processed');
    }
  }
};

/**
 * AHAH object.
 */
Drupal.ahah = function(base, element) {
  // Set the properties for this object.
  this.id = '#' + base;
  this.event = element.event;
  this.uri = element.uri;
  this.wrapper = '#'+ element.wrapper;
  this.effect = element.effect;
  this.method = element.method;
  if (this.effect == 'none') {
    this.showEffect = 'show';
    this.hideEffect = 'hide';
  }
  else if (this.effect == 'fade') {
    this.showEffect = 'fadeIn';
    this.hideEffect = 'fadeOut';
  }
  else {
    this.showEffect = this.effect + 'Toggle';
    this.hideEffect = this.effect + 'Toggle';
  }
  Drupal.redirectFormButton(this.uri, $(this.id).get(0), this);
};

/**
 * Handler for the form redirection submission.
 */
Drupal.ahah.prototype.onsubmit = function () {
  // Insert progressbar and stretch to take the same space.
  this.progress = new Drupal.progressBar('ahah_progress');
  this.progress.setProgress(-1, Drupal.t('Please wait...'));

  var wrapper = $(this.wrapper);
  var button = $(this.id);
  var progress_element = $(this.progress.element);

  progress_element.css('float', 'left').css({
    display: 'none',
    width: '10em',
    margin: '0 0 0 20px'
  });
  button.css('float', 'left').attr('disabled', true).after(progress_element);
  eval('progress_element.' + this.showEffect + '()');
};

/**
 * Handler for the form redirection completion.
 */
Drupal.ahah.prototype.oncomplete = function (data) {
  var wrapper = $(this.wrapper);
  var button = $(this.id);
  var progress_element = $(this.progress.element);
  var new_content = $('<div>' + data + '</div>');

  Drupal.freezeHeight();

  // Remove the progress element.
  progress_element.remove();

  // Hide the new content before adding to page.
  new_content.hide();

  // Add the form and re-attach behavior.
  if (this.method == 'replace') {
    wrapper.empty().append(new_content);
  }
  else {
    eval('wrapper.' + this.method + '(new_content)');
  }
  eval('new_content.' + this.showEffect + '()');
  button.css('float', 'none').attr('disabled', false);

  Drupal.attachBehaviors(new_content);
  Drupal.unfreezeHeight();
};

/**
 * Handler for the form redirection error.
 */
Drupal.ahah.prototype.onerror = function (error) {
  alert(Drupal.t('An error occurred:\n\n@error', { '@error': error }));
  // Remove progressbar.
  $(this.progress.element).remove();
  this.progress = null;
  // Undo hide.
  $(this.wrapper).show();
  // Re-enable the element.
  $(this.id).css('float', 'none').attr('disabled', false);
};
