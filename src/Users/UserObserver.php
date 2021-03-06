<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Users;


use WildPHP\Core\Channels\ChannelModes;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\Database\Database;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Core\StateException;
use WildPHP\Messages\Join;
use WildPHP\Messages\Nick;
use WildPHP\Messages\RPL\EndOfNames;
use WildPHP\Messages\RPL\NamReply;
use WildPHP\Messages\RPL\WhosPcRpl;

class UserObserver extends BaseModule
{

    /**
     * BaseModule constructor.
     *
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function __construct(ComponentContainer $container)
    {
        $this->setContainer($container);

        EventEmitter::fromContainer($container)->on(
            'irc.line.in.join', [$this, 'processUserJoin']);

        // 353: RPL_NAMREPLY
        EventEmitter::fromContainer($container)->on(
            'irc.line.in.353', [$this, 'processNamesReply']);

        // 366: RPL_ENDOFNAMES
        EventEmitter::fromContainer($container)->on(
            'irc.line.in.366', [$this, 'sendInitialWhoxMessage']);

        // 354: RPL_WHOSPCRPL
        EventEmitter::fromContainer($container)->on(
            'irc.line.in.354', [$this, 'processWhoxReply']);

        EventEmitter::fromContainer($container)->on(
            'irc.line.in.nick', [$this, 'processUserNickChange']);
    }

    /**
     * @param JOIN $joinMessage
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function processUserJoin(JOIN $joinMessage)
    {
        $db = Database::fromContainer($this->getContainer());

        if (!$db->has('users', ['nickname' => $joinMessage->getNickname()])) {
            $prefix = $joinMessage->getPrefix();
            $db->insert('users', [
                'nickname' => $joinMessage->getNickname(),
                'username' => $prefix->getUsername(),
                'hostname' => $prefix->getHostname(),
                'irc_account' => $joinMessage->getIrcAccount(),
            ]);

            Logger::fromContainer($this->getContainer())->debug('Added user', [
                'reason' => 'join',
                'nickname' => $joinMessage->getNickname(),
                'username' => $prefix->getUsername(),
                'hostname' => $prefix->getHostname(),
                'irc_account' => $joinMessage->getIrcAccount()
            ]);
        }
    }

    /**
     * @param NamReply $ircMessage
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws StateException
     */
    public function processNamesReply(NamReply $ircMessage)
    {
        $db = Database::fromContainer($this->getContainer());
        $nicknames = $ircMessage->getNicknames();

        $modePrefixes = ($result = $db->get('server_config', ['value'],
            ['key' => 'PREFIX'])) ? $result['value'] : '(ohv)@%+';

        foreach ($nicknames as $nicknameWithMode) {
            $nickname = '';
            $modes = ChannelModes::extractUserModesFromNickname($modePrefixes, $nicknameWithMode, $nickname);

            if (!$db->has('users', ['nickname' => $nickname])) {
                $db->insert('users', [
                    'nickname' => $nickname
                ]);
                $userID = $db->id();
            } else {
                $userID = $db->get('users', ['id'], ['nickname' => $nickname])['id'];
            }

            if (!$db->has('channels', ['name' => $ircMessage->getChannel()])) {
                throw new StateException('Channel not found, but RPL_NAMES received; state mismatch!');
            }

            $channelID = $db->get('channels', ['id'], ['name' => $ircMessage->getChannel()])['id'];

            foreach ($modes as $mode) {
                $db->insert('mode_relations', [
                    'user_id' => $userID,
                    'channel_id' => $channelID,
                    'mode' => $mode
                ]);
            }

            Logger::fromContainer($this->getContainer())->debug('Modified or created user',
                ['reason' => 'rpl_namreply', 'nickname' => $nickname, 'modes' => $modes]);
        }
    }

    /**
     * @param EndOfNames $ircMessage
     * @param Queue $queue
     */
    public function sendInitialWhoxMessage(EndOfNames $ircMessage, Queue $queue)
    {
        $channel = $ircMessage->getChannel();
        $queue->who($channel, '%nuhaf');
    }

    /**
     * @param WhosPcRpl $ircMessage
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws StateException
     * @throws UserNotFoundException
     */
    public function processWhoxReply(WhosPcRpl $ircMessage)
    {
        $db = Database::fromContainer($this->getContainer());

        if (!$db->has('users', ['nickname' => $ircMessage->getNickname()])) {
            throw new StateException('RPL_WHOSPCRPL received but user was not found... Impossible!');
        }

        $user = User::fromDatabase($db, ['nickname' => $ircMessage->getNickname()]);
        $user->setNickname($ircMessage->getNickname());
        $user->setUsername($ircMessage->getUsername());
        $user->setHostname($ircMessage->getHostname());
        $user->setIrcAccount($ircMessage->getAccountname());
        User::toDatabase($db, $user);

        Logger::fromContainer($this->getContainer())->debug('Modified user',
            array_merge(['reason' => 'rpl_whospcrpl'], $user->toArray()));
    }

    /**
     * @param NICK $nickMessage
     * @throws StateException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function processUserNickChange(NICK $nickMessage)
    {
        $db = Database::fromContainer($this->getContainer());

        $userID = $db->get('users', ['id'], ['nickname' => $nickMessage->getNickname()]);

        if (!$userID) {
            throw new StateException('Nick change detected but I have no idea who this user is... Help!');
        }

        $db->update('users', [
            'nickname' => $nickMessage->getNewNickname()
        ], ['id' => $userID]);

        Logger::fromContainer($this->getContainer())->debug('Updated user nickname', [
            'oldNickname' => $nickMessage->getNickname(),
            'nickname' => $nickMessage->getNewNickname()
        ]);
    }

    /**
     * @return string
     */
    public static function getSupportedVersionConstraint(): string
    {
        return WPHP_VERSION;
    }

    /**
     * @return array
     */
    public static function getDependentModules(): array
    {
        return [
            Logger::class,
            Database::class,
            EventEmitter::class
        ];
    }
}