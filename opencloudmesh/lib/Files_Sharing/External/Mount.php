<?php
/**
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2023, SURF
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenCloudMesh\Files_Sharing\External;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

class Mount extends MountPoint implements MoveableMount {
	/**
	 * @var \OCA\OpenCloudMesh\Files_Sharing\External\Manager
	 */
	protected $manager;

	/**
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $options
	 * @param \OCA\OpenCloudMesh\Files_Sharing\External\Manager $manager
	 * @param \OC\Files\Storage\StorageFactory $loader
	 */
	public function __construct($storage, $mountpoint, $options, $manager, $loader = null) {
		parent::__construct($storage, $mountpoint, $options, $loader);
		$this->manager = $manager;
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$result = $this->manager->setMountPoint($this->mountPoint, $target);
		if ($result === true) {
			$this->setMountPoint($target);
		}
		return $result;
	}

	/**
	 * Remove the mount points
	 *
	 * @return mixed
	 * @return bool
	 */
	public function removeMount() {
		return $this->manager->removeShare($this->mountPoint);
	}

	/**
	 * Returns true
	 *
	 * @param string $target unused
	 * @return bool true
	 */
	public function isTargetAllowed($target) {
		// note: home storage check already done in View
		return true;
	}
}
