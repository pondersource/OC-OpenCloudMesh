<?php

namespace OCA\OpenCloudMesh\Hooks;

// use OCP\IGroupManager;

class UserHooks {
    private $userSession;
    private $userManager;
    private $groupManager;
    private $config;
    private $globalAutoAcceptValue;

    public function __construct($userSession, $userManager, $groupManager, $config) {
        $this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
        $this->config = $config;

        $this->globalAutoAcceptValue  = $this->config->getAppValue('federatedfilesharing','auto_accept_trusted','no');
    }

    public function register() {
        

        $callback = function ($user) {
            $user =  $this->userManager->get($user);
            $userGroups =  $this->groupManager->getUserGroups($user);

            foreach ($userGroups as $group) {
                $groupId = $group->getGID();

                error_log('-------------------');
                error_log(json_encode($group->getGID()));
                error_log('-------------------');
            }
        };
        $this->userSession->listen('\OC\User', 'preLogin', $callback);
    }
}
