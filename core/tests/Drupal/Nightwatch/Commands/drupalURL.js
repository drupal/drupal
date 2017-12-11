exports.command = function baseurl(relativeURL) {
  if (!process.env.BASE_URL || process.env.BASE_URL === '') {
    throw new Error('Missing a BASE_URL environment variable.');
  }
  this
    .url(`${process.env.BASE_URL}${relativeURL}`);
  return this;
};
