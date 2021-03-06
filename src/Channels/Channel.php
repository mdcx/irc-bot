<?php

/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Channels;

use WildPHP\Core\Database\Database;
use WildPHP\Core\StateException;

class Channel
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $topic = '';

    /**
     * @var string
     */
    protected $createdBy = '';

    /**
     * @var int
     */
    protected $createdTime = 0;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * Channel constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return bool
     */
    public static function isValidName(string $name, string $prefix)
    {
        return substr($name, 0, strlen($prefix)) == $prefix;
    }

    /**
     * @param Database $db
     * @param array $where
     * @return static
     * @throws ChannelNotFoundException
     * @throws StateException
     */
    public static function fromDatabase(Database $db, array $where = [])
    {
        if (!$db->has('channels', $where)) {
            throw new ChannelNotFoundException();
        }

        $data = $db->get('channels', ['id', 'name', 'topic'], $where);

        if (!$data) {
            throw new StateException('Tried to get 1 channel from database but received none or multiple... State mismatch!');
        }

        $channel = new Channel($data['name']);
        $channel->setTopic($data['topic']);
        $channel->setId($data['id']);
        return $channel;
    }

    /**
     * @param Database $db
     * @param Channel $channel
     * @return int The user id
     */
    public static function toDatabase(Database $db, Channel $channel): int
    {
        $data = $channel->toArray();

        if (empty($channel->getId()) || !$db->has('channels', [], ['id' => $channel->getId()])) {
            // unset the id here so we don't overwrite or cause a potential error
            unset($data['id']);
            $db->insert('channels', [$data]);

            return (int)$db->id();
        }

        $db->update('users', $data, ['id' => $channel->getId()]);
        return $channel->getId();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'topic' => $this->getTopic(),
            'created_by' => $this->getCreatedBy(),
            'created_time' => $this->getCreatedTime()
        ];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    protected function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic(string $topic)
    {
        $this->topic = $topic;
    }

    /**
     * @return string
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return int
     */
    public function getCreatedTime(): int
    {
        return $this->createdTime;
    }

    /**
     * @param int $createdTime
     */
    public function setCreatedTime(int $createdTime)
    {
        $this->createdTime = $createdTime;
    }
}