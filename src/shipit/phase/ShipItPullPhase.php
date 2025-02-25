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
 * https://fburl.com/83mi107w
 */
namespace Facebook\ShipIt;

final class ShipItPullPhase extends ShipItPhase {
  public function __construct(private ShipItRepoSide $side) {}

  <<__Override>>
  public function getReadableName(): string {
    return 'Pull '.$this->side.' repository';
  }

  <<__Override>>
  public function getCLIArguments(): vec<ShipItCLIArgument> {
    return vec[
      shape(
        'long_name' => 'skip-'.$this->side.'-pull',
        'description' => "Don't pull the ".$this->side." repository",
        'write' => (string $_) ==> $this->skip(),
      ),
    ];
  }

  <<__Override>>
  protected async function genRunImpl(
    ShipItManifest $manifest,
  ): Awaitable<void> {
    switch ($this->side) {
      case ShipItRepoSide::SOURCE:
        $lock = $manifest->getSourceSharedLock();
        $local_path = $manifest->getSourcePath();
        $branch = $manifest->getSourceBranch();
        break;
      case ShipItRepoSide::DESTINATION:
        $lock = $manifest->getDestinationSharedLock();
        $local_path = $manifest->getDestinationPath();
        $branch = $manifest->getDestinationBranch();
        break;
    }
    $repo = await ShipItRepo::genOpen($lock, $local_path, $branch);
    await $repo->genPull();
  }
}
