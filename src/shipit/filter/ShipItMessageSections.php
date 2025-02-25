<?hh
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 * @format
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/nhndf5h3
 */
namespace Facebook\ShipIt;

enum ShipItMessageSectionHeaders: string as string {
  // @lint-ignore[HackLint5520] Intentionally breaking naming conventions
  _NO_HEADER = '';
  SUMMARY = 'summary';
  FACEBOOK = 'facebook';
  GITHUB_AUTHOR = 'github author';
  GITHUB_PR_SYNC = 'github pr sync';
  TEST_PLAN = 'test plan';
  TESTS = 'tests'; // Phabricator alias for Test Plan
  REVIEWERS = 'reviewers';
  REVIEWED_BY = 'reviewed by';
  DIFFERENTIAL_REVISION = 'differential revision';
  BLAME_REVISION = 'blame revision';
  TAGS = 'tags';
  TASK_ID = 'task id';
  TASKS = 'tasks';
  SUBSCRIBERS = 'subscribers';
  CC = 'cc'; // Phabricator alias for Subscribers
  SIGNATURE = 'signature';
  COMMITTER = 'committer';
  PULLED_BY = 'pulled by';
  PULL_REQUEST_RESOLVED = 'pull request resolved';
}

use namespace HH\Lib\{C, Dict, Str, Regex}; // @oss-enable

/** Utility class for commit messages with sections preceded by "Header: ".
 *
 * For example, Phabricator creates messages like:
 * Summary:
 *   Foo bar
 * Test Plan:
 *   Baz
 */
final class ShipItMessageSections {
  /** Get a dict[$header => $content] of sections.
   *
   * @param $valid_sections what sections are real sections; if specified, and
   *   something that looks like a section header is seen that isn't in this
   *   list, it will be considered content. If unspecified, every line like
   *   /^[a-zA-Z ]+:/ will be considered a header. All headers should be
   *   lowercase.
   */
  public static function getSections(
    ShipItChangeset $changeset,
    ?keyset<ShipItMessageSectionHeaders> $valid_sections = null,
  ): dict<string, string> {
    $sections = dict[ShipItMessageSectionHeaders::_NO_HEADER => ''];
    $section = '';
    foreach (Str\split($changeset->getMessage(), "\n") as $line) {
      $line = Str\trim_right($line);
      if (Regex\matches($line, re'/^[a-zA-Z ]+:/')) {
        $h = Str\lowercase(Str\slice($line, 0, Str\search($line, ':')));
        if ($valid_sections === null || C\contains($valid_sections, $h)) {
          $section = $h;
          $value = Str\trim(Str\slice($line, Str\length($section) + 1));

          // Treat "Summary: FBOnly: bar" as "FBOnly: bar" - handy if using
          // Phabricator
          if (
            Regex\matches($value, re'/^[a-zA-Z ]+:/') &&
            $valid_sections !== null
          ) {
            $h = Str\lowercase(Str\slice($value, 0, Str\search($value, ':')));
            if (C\contains($valid_sections, $h)) {
              $section = $h;
              $value = Str\trim(Str\slice($value, Str\length($section) + 1));
            }
          }
          if (C\contains_key($sections, $section)) {
            $sections[$section] .= $value;
          } else {
            $sections[$section] = $value;
          }
          continue;
        }
      }
      $sections[$section] .= "\n{$line}";
    }
    if ($sections[""] === '') {
      unset($sections['']);
    }

    return Dict\map($sections, $x ==> Str\trim($x));
  }

  /** Convert a section map back to a commit message */
  public static function buildMessage(dict<string, string> $sections): string {
    $out = '';
    foreach ($sections as $section => $text) {
      if (PHP\ctype_space($text) || $text === '') {
        continue;
      }
      $section_head = Str\capitalize_words($section).":";
      $text = Str\trim($text);
      if (!self::hasMoreThanOneNonEmptyLine($text)) {
        $section_head .= ' ';
      } else {
        $section_head .= "\n";
      }
      $out .= $section_head."$text\n\n";
    }
    return Str\trim_right($out);
  }

  private static function hasMoreThanOneNonEmptyLine(string $str): bool {
    $lines = Str\split($str, "\n");
    $cn = 0;
    foreach ($lines as $line) {
      if (!(PHP\ctype_space($line) || $line === '')) {
        ++$cn;
      }
    }
    return $cn > 1;
  }
}
