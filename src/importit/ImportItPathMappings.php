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
 * https://fburl.com/fylti1vi
 */
namespace Facebook\ImportIt;

/**
 * An interface a ShipIt CLI class would want to implement if it uses ImportIt
 * to pull changes into an internal repository.
 */
interface ImportItPathMappings {
  /**
   * A map from directory paths in the source repository to paths in the
   * destination repository. The first matching mapping is used.
   */
  public static function getPathMappings(): dict<string, string>;
}
