<?php
/**
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 * @author Yashar PM <yashar@pondersource.com>
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

namespace OCA\OpenCloudMesh\Tests\FederatedFileSharing;

use OC\Files\Filesystem;
use OCA\FederatedFileSharing\Address;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\OpenCloudMesh\FederatedUserShareProvider;
use OCA\OpenCloudMesh\FederatedFileSharing\FedUserShareManager;
use OCA\OpenCloudMesh\FederatedFileSharing\UserNotifications;
use OCA\FederatedFileSharing\Ocm\Permissions;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as ActivityManager;
use OCP\IUserManager;
use OCP\Notification\IAction;
use OCP\Notification\IManager as NotificationManager;
use OCP\Notification\INotification;
use OCP\Share\IShare;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Traits\UserTrait;

/**
 * Class FedUserShareManagerTest
 *
 * @package OCA\OpenCloudMesh\FederatedFileSharing\Tests
 * @group DB
 */
class FedUserShareManagerTest extends TestCase {
	/** @var FederatedUserShareProvider | \PHPUnit\Framework\MockObject\MockObject */
	private $federatedShareProvider;

	/** @var UserNotifications | \PHPUnit\Framework\MockObject\MockObject */
	private $notifications;

	/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var ActivityManager | \PHPUnit\Framework\MockObject\MockObject */
	private $activityManager;

	/** @var NotificationManager | \PHPUnit\Framework\MockObject\MockObject */
	private $notificationManager;

	/** @var FedUserShareManager | \PHPUnit\Framework\MockObject\MockObject */
	private $fedShareManager;

	/** @var AddressHandler | \PHPUnit\Framework\MockObject\MockObject */
	private $addressHandler;

	/** @var Permissions | \PHPUnit\Framework\MockObject\MockObject */
	private $permissions;

	/** @var EventDispatcherInterface | \PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedShareProvider = $this->getMockBuilder(
			FederatedUserShareProvider::class
		)->disableOriginalConstructor()->getMock();
		$this->notifications = $this->getMockBuilder(UserNotifications::class)
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->getMock();
		$this->activityManager = $this->getMockBuilder(ActivityManager::class)
			->getMock();
		$this->notificationManager = $this->getMockBuilder(NotificationManager::class)
			->getMock();
		$this->addressHandler = $this->getMockBuilder(AddressHandler::class)
			->disableOriginalConstructor()->getMock();

		$this->permissions = $this->createMock(Permissions::class);

		$this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
			->getMock();

		$this->fedShareManager = $this->getMockBuilder(FedUserShareManager::class)
			->setConstructorArgs(
				[
					$this->federatedShareProvider,
					$this->notifications,
					$this->userManager,
					$this->activityManager,
					$this->notificationManager,
					$this->addressHandler,
					$this->permissions,
					$this->eventDispatcher
				]
			)
			->setMethods(['getFile'])
			->getMock();
	}

	public function testCreateShare() {
		$shareWith = 'Bob';
		$owner = 'Alice';
		$ownerFederatedId = 'server2';
		$sharedByFederatedId = 'server3';
		$sharedBy = 'Steve';
		$ownerAddress = new Address("$owner@$ownerFederatedId");
		$sharedByAddress = new Address("$sharedBy@$sharedByFederatedId");
		$remoteId = 42;
		$name = 'file.ext';
		$token = 'idk';

		$event = $this->getMockBuilder(IEvent::class)->getMock();
		$event->method($this->anything())
			->willReturnSelf();
		$event->expects($this->any())
			->method($this->anything())
			->willReturnSelf();

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);

		$action = $this->getMockBuilder(IAction::class)->getMock();
		$action->method($this->anything())->willReturnSelf();
		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->method('createAction')->willReturn($action);
		$notification->method($this->anything())
			->willReturnSelf();

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);

		$this->fedShareManager->createShare(
			$ownerAddress,
			$sharedByAddress,
			$shareWith,
			$remoteId,
			$name,
			$token
		);
	}

	public function testAcceptShare() {
		$this->fedShareManager->expects($this->once())
			->method('getFile')
			->willReturn(['/file','http://file']);

		$node = $this->getMockBuilder(\OCP\Files\File::class)
			->disableOriginalConstructor()->getMock();
		$node->expects($this->once())
			->method('getId')
			->willReturn(42);

		$share = $this->getMockBuilder(IShare::class)->getMock();
		$share->expects($this->once())
			->method('getNode')
			->willReturn($node);

		$event = $this->getMockBuilder(IEvent::class)->getMock();
		$event->method($this->anything())
			->willReturnSelf();
		$event->expects($this->any())
			->method($this->anything())
			->willReturnSelf();

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);

		$this->fedShareManager->acceptShare($share);
	}

	public function testDeclineShare() {
		$this->fedShareManager->expects($this->once())
			->method('getFile')
			->willReturn(['/file','http://file']);

		$node = $this->getMockBuilder(\OCP\Files\File::class)
			->disableOriginalConstructor()->getMock();
		$node->expects($this->once())
			->method('getId')
			->willReturn(42);

		$share = $this->getMockBuilder(IShare::class)->getMock();
		$share->expects($this->once())
			->method('getNode')
			->willReturn($node);
		$share->method('getShareOwner')
			->willReturn('Alice');
		$share->method('getSharedBy')
			->willReturn('Bob');

		$this->notifications->expects($this->once())
			->method('sendDeclineShare');

		$event = $this->getMockBuilder(IEvent::class)->getMock();
		$event->method($this->anything())
			->willReturnSelf();
		$event->expects($this->any())
			->method($this->anything())
			->willReturnSelf();

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);

		$this->fedShareManager->declineShare($share);
	}

	public function testUnshare() {
		$shareRow = [
			'id' => 42,
			'remote' => 'peer',
			'remote_id' => 142,
			'share_token' => 'abc',
			'password' => '',
			'name' => 'McGee',
			'owner' => 'Alice',
			'user' => 'Bob',
			'mountpoint' => '/mount/',
			'accepted' => 1
		];
		$this->federatedShareProvider
			->method('unshare')
			->willReturn($shareRow);

		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->method($this->anything())
			->willReturnSelf();

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);

		$event = $this->getMockBuilder(IEvent::class)->getMock();
		$event->method($this->anything())
			->willReturnSelf();
		$event->expects($this->any())
			->method($this->anything())
			->willReturnSelf();

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);

		$this->fedShareManager->unshare($shareRow['id'], $shareRow['share_token']);
	}

	public function testReshareUndo() {
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()->getMock();
		$this->federatedShareProvider->expects($this->once())
			->method('removeShareFromTable')
			->with($share);
		$this->fedShareManager->undoReshare($share);
	}

	public function testIsFederatedReShare() {
		$shareInitiator = 'user';
		$share = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()->getMock();
		$share->expects($this->any())
			->method('getSharedBy')
			->willReturn($shareInitiator);

		$nodeMock = $this->getMockBuilder('OCP\Files\Node')
			->disableOriginalConstructor()->getMock();
		$share->expects($this->once())
			->method('getNode')
			->willReturn($nodeMock);

		$otherShare = $this->getMockBuilder(IShare::class)
			->disableOriginalConstructor()->getMock();
		$otherShare->expects($this->any())
			->method('getSharedWith')
			->willReturn($shareInitiator);

		$this->federatedShareProvider->expects($this->once())
			->method('getSharesByPath')
			->willReturn([$share, $otherShare]);

		$isFederatedShared = $this->fedShareManager->isFederatedReShare($share);
		$this->assertEquals(
			true,
			$isFederatedShared
		);
	}
}
