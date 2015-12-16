Poltergeist.JavascriptError = (function (_super) {
  __extends(JavascriptError, _super);

  function JavascriptError(errors) {
    this.errors = errors;
  }

  JavascriptError.prototype.name = "Poltergeist.JavascriptError";

  JavascriptError.prototype.args = function () {
    return [this.errors];
  };

  return JavascriptError;

})(Poltergeist.Error);
