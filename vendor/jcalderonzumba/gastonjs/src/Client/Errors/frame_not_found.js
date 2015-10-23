Poltergeist.FrameNotFound = (function (_super) {
  __extends(FrameNotFound, _super);

  function FrameNotFound(frameName) {
    this.frameName = frameName;
  }

  FrameNotFound.prototype.name = "Poltergeist.FrameNotFound";

  FrameNotFound.prototype.args = function () {
    return [this.frameName];
  };

  return FrameNotFound;

})(Poltergeist.Error);
