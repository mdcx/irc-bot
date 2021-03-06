<?php

/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Management;


use WildPHP\Commands\Command;
use WildPHP\Commands\Parameters\StringParameter;
use WildPHP\Commands\ParameterStrategy;
use WildPHP\Core\Channels\Channel;
use WildPHP\Core\Commands\CommandRegistrar;
use WildPHP\Core\Commands\JoinedChannelParameter;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\Database\Database;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Core\Users\User;

class ManagementCommands extends BaseModule
{
    use ContainerTrait;

    /**
     * ManagementCommands constructor.
     *
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function __construct(ComponentContainer $container)
    {
        CommandRegistrar::fromContainer($container)->register('join',
            new Command(
                [$this, 'joinCommand'],
                new ParameterStrategy(1, 5, [
                    'channel1' => new StringParameter(),
                    'channel2' => new StringParameter(),
                    'channel3' => new StringParameter(),
                    'channel4' => new StringParameter(),
                    'channel5' => new StringParameter()
                ])
            ));

        CommandRegistrar::fromContainer($container)->register('part',
            new Command(
                [$this, 'partCommand'],
                new ParameterStrategy(0, 5, [
                    'channel1' => new JoinedChannelParameter(Database::fromContainer($container)),
                    'channel2' => new JoinedChannelParameter(Database::fromContainer($container)),
                    'channel3' => new JoinedChannelParameter(Database::fromContainer($container)),
                    'channel4' => new JoinedChannelParameter(Database::fromContainer($container)),
                    'channel5' => new JoinedChannelParameter(Database::fromContainer($container))
                ])
            ));

        CommandRegistrar::fromContainer($container)->register('quit',
            new Command(
                [$this, 'quitCommand'],
                new ParameterStrategy(0, -1, [
                    'message' => new StringParameter()
                ], true)
            ));

        CommandRegistrar::fromContainer($container)->register('nick',
            new Command(
                [$this, 'nickCommand'],
                new ParameterStrategy(0, -1, [
                    'newNickname' => new StringParameter()
                ])
            ));

        CommandRegistrar::fromContainer($container)->register('clearqueue',
            new Command(
                [$this, 'clearqueueCommand'],
                new ParameterStrategy(0, 0)
            ));

        $this->setContainer($container);
    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @param Channel $source
     * @param User $user
     * @param $args
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function quitCommand(Channel $source, User $user, $args, ComponentContainer $container)
    {
        $message = implode(' ', $args);

        if (empty($message)) {
            $message = 'Quit command given by ' . $user->getNickname();
        }

        Queue::fromContainer($container)
            ->quit($message);
    }

    /**
     * @param Channel $source
     * @param User $user
     * @param $channels
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function joinCommand(Channel $source, User $user, $channels, ComponentContainer $container)
    {
        $validChannels = $this->validateChannels($channels);

        if (!empty($validChannels)) {
            Queue::fromContainer($container)
                ->join($validChannels);
        }

        $diff = array_diff($channels, $validChannels);

        if (!empty($diff)) {
            Queue::fromContainer($container)
                ->privmsg($user->getNickname(),
                    'Did not join the following channels because they do not follow proper formatting: ' . implode(', ',
                        $diff));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @param array $channels
     *
     * @return array
     * @throws \Yoshi2889\Container\NotFoundException
     */
    protected function validateChannels(array $channels): array
    {
        $validChannels = [];
        $serverChannelPrefix = Configuration::fromContainer($this->getContainer())['serverConfig']['chantypes'];

        foreach ($channels as $channel) {
            if (substr($channel, 0, strlen($serverChannelPrefix)) != $serverChannelPrefix) {
                continue;
            }

            $validChannels[] = $channel;
        }

        return $validChannels;
    }

    /**
     * @param Channel $source
     * @param User $user
     * @param $channels
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function partCommand(Channel $source, User $user, $channels, ComponentContainer $container)
    {
        if (empty($channels)) {
            $channels = [$source];
        }

        /**
         * @var int $index
         * @var Channel $channel
         */
        foreach ($channels as $index => $channel) {
            $channels[$index] = $channel->getName();
        }

        $validChannels = $this->validateChannels($channels);

        if (!empty($validChannels)) {
            Queue::fromContainer($container)
                ->part($validChannels);
        }

        $diff = array_diff($channels, $validChannels);

        if (!empty($diff)) {
            Queue::fromContainer($container)
                ->privmsg($user->getNickname(),
                    'Did not part the following channels because they do not follow proper formatting: ' . implode(', ',
                        $diff));
        }
    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @param Channel $source
     * @param User $user
     * @param array $args
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function nickCommand(Channel $source, User $user, array $args, ComponentContainer $container)
    {
        // TODO: Validate
        Queue::fromContainer($container)->nick($args['newNickname']);
    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @param Channel $source
     * @param User $user
     * @param array $args
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function clearqueueCommand(Channel $source, User $user, array $args, ComponentContainer $container)
    {
        Queue::fromContainer($container)->clear();
        Queue::fromContainer($container)->privmsg($source->getName(),
            $user->getNickname() . ': Message queue cleared.');
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
            CommandRegistrar::class,
            Queue::class,
            Database::class
        ];
    }
}