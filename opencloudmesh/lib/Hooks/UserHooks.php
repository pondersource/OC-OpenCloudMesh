<?php

namespace OCA\OpenCloudMesh\Hooks;

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCA\OpenCloudMesh\Files_Sharing\External\Manager;

class UserHooks
{
    private IConfig $config;
    private IUserSession $userSession;
    private IUserManager $userManager;
    private IGroupManager $groupManager;
    private Manager $externalManager;
    protected IDBConnection $connection;

    public function __construct(
        IConfig $config,
        IUserSession $userSession,
        IUserManager $userManager,
        IGroupManager $groupManager,
        Manager $externalManager,
        IDBConnection $connection
    ) {
        $this->config = $config;
        $this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->externalManager = $externalManager;
        $this->connection = $connection;
    }

    public function register()
    {
        $globalAutoAcceptValue = $this->config->getAppValue('federatedfilesharing', 'auto_accept_trusted', 'no');
        $callback = function ($user) {
            $user = $this->userManager->get($user);
            $userId = $user->getUID();
            \OC_Util::setupFS($userId);

            $userGroups = $this->groupManager->getUserGroups($user);

            foreach ($userGroups as $group) {
                $groupId = $group->getGID();

                $getGroupSharesStmt = $this->connection->prepare("SELECT * FROM  `*PREFIX*share_external_group` WHERE `user` = ?");

                $getUserSharesStmt = $this->connection->prepare("SELECT * FROM  `*PREFIX*share_external_group` WHERE `user` = ?");

                $groupShares = $getGroupSharesStmt->execute([$groupId]) ? $getGroupSharesStmt->fetchAll() : [];
                $userShares = $getUserSharesStmt->execute([$userId]) ? $getUserSharesStmt->fetchAll() : [];

                $openShares = array_diff($userShares, $groupShares);


                $openShares = array_filter($groupShares, function ($groupShare) use ($userShares) {
                    return !array_filter($userShares, function ($userShare) use ($groupShare) {
                        return $userShare['parent'] == $groupShare['id'];
                    });
                });


                foreach ($openShares as $share) {
                    $shareFolder = \OCA\Files_Sharing\Helper::getShareFolder();
                    $mountPoint = \OCP\Files::buildNotExistingFileName($shareFolder, $share['name']);
                    $mountPoint = \OC\Files\Filesystem::normalizePath($mountPoint);
                    $mountpoint_hash = \md5($mountPoint);

                    $query = $this->connection->prepare("
                        INSERT INTO oc_share_external_group
                        (`parent`, `remote`,`remote_id`, `share_token`, `password`, `name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`, `accepted`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $query->execute([
                        $share['id'],
                        $share['remote'],
                        $share['remote_id'],
                        $share['share_token'],
                        $share['password'],
                        $share['name'],
                        $share['owner'],
                        $userId,
                        $mountPoint,
                        $mountpoint_hash,
                        1,
                    ]);
                }
            }
        };
        if ($globalAutoAcceptValue === 'yes') {
            $this->userSession->listen('\OC\User', 'preLogin', $callback);
        }
    }

    private function acceptRemoteGroupSharesAutomatically($user)
    {
       
    }
}