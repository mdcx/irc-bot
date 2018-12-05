<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Observers;


use Evenement\EventEmitterInterface;
use Psr\Log\LoggerInterface;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Entities\IrcUser;

class BotNicknameObserver
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * BotStateManager constructor.
     *
     * @param Configuration $configuration
     * @param EventEmitterInterface $eventEmitter
     * @param LoggerInterface $logger
     */
    public function __construct(Configuration $configuration, EventEmitterInterface $eventEmitter, LoggerInterface $logger)
    {
        $eventEmitter->on('user.nick', [$this, 'monitorBotNickname']);

        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @param IrcUser $user
     * @param string $oldNickname
     * @param string $newNickname
     */
    public function monitorBotNickname(IrcUser $user, string $oldNickname, string $newNickname)
    {
        if ($oldNickname != $this->configuration['currentNickname']) {
            return;
        }

        $this->configuration['currentNickname'] = $newNickname;

        $this->logger->debug('Updated current nickname for bot', [
            'oldNickname' => $oldNickname,
            'newNickname' => $newNickname
        ]);
    }
}