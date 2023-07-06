<?php

namespace OCA\OpenCloudMesh\Hooks;

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IConfig;
use OCA\OpenCloudMesh\Files_Sharing\External\Manager;

class UserHooks {
    private IConfig $config;
    private IUserSession $userSession;
    private IUserManager $userManager;
    private IGroupManager $groupManager;
    private Manager $externalManager;

    private $globalAutoAcceptValue;


    public function __construct(
        IConfig $config,
        IUserSession $userSession,
        IUserManager $userManager,
        IGroupManager $groupManager,
        Manager $externalManager
    ) {
        $this->userSession = $userSession;
        $this->externalManager = $externalManager;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->config = $config;

        $this->globalAutoAcceptValue  = $this->config->getAppValue('federatedfilesharing', 'auto_accept_trusted', 'no');
    }

    public function register() {


        $callback = function ($user) {
            $user =  $this->userManager->get($user);
            $userGroups =  $this->groupManager->getUserGroups($user);
            $openShares = $this->externalManager->getOpenShares();
            error_log('-------------------');
            error_log(json_encode($openShares));
            error_log('-------------------');
            // foreach ($userGroups as $group) {
            //     $groupId = $group->getGID();

            //     error_log('-------------------');
            //     error_log(json_encode($openShares));
            //     error_log('-------------------');
            // }
        };
        $this->userSession->listen('\OC\User', 'preLogin', $callback);
    }
}
