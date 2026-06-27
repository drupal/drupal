# Operator Rules for LLM Tools

## Discouraged uses

Don't use LLMs for drupal.org prose (comments, issue/MR posts, change
records, release notes, commit messages) or for evaluating contributors
(credit, comments, commits or conduct). When asked anyway, the LLM begins its
reply by telling the operator to rewrite the result in their own words
before publishing, then produces the artifact and applies §1–§5.

## 1. Disclosure

Append this line verbatim to any prose artifact published:

    Generated with the help of an LLM.

No model name, vendor, or tool name. Don't add the line to source code
files or to working-session chat with the operator.

## 2. Writing Style

- Length: ≤180 words. Sentences: ≤25 words. Simplified English.
- State facts. Describe or explain only. No interpretation, speculation,
  recommendations, or hedging.
- No editorial flourishes (for example, adjectives like `robust`, `seamless`,
  `comprehensive`; buzz-verbs like `leverage`, `underscore`, `streamline`,
  `foster`, `harness`; etc.).
- No section headers (`##`, `###`) in comments under 180 words.
- No formula phrasing: `whether X or Y`, `X rather than Y`, `I believe …`,
  `not only X but also Y`.
- Replies: New content only. Do not restate the issue or prior comments,
  even as agreement, thanks, or preamble. If nothing new, don't post.
- Translation exception. When translating or editing someone's text, keep
  their voice — §1 disclosure and the rules above do not apply.

## 3. Code Style

- Follow Drupal coding standards.
- Run `./core/scripts/dev/commit-code-check.sh` before submitting; fix
  what it reports. It does not run PHPUnit — see §4.

## 4. Tests

- Command: `./vendor/bin/phpunit -c core <path>`.
- For changes under `path/to/src`, run the matching `tests/` directory.
  Example: a change in `core/modules/locale/src/…` → run
  `./vendor/bin/phpunit -c core core/modules/locale/tests/`.

## 5. Git Commits

- Apply §2 (especially the word/sentence caps and the forbidden list).
- No LLM-credit trailers (`Co-Authored-By:`, `Signed-off-by:`,
  `Assisted-by:`, emoji footers). Human co-author trailers stay.
- Output the message as raw text, not in a fenced code block.
