<?php

/*
	WildPHP - a modular and easily extendable IRC bot written in PHP
	Copyright (C) 2016 WildPHP

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WildPHP\Core\Connection\Commands;


class Join extends BaseCommand
{
	/**
	 * @var string
	 */
	protected $channel;

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @param string $channel
	 * @param string $key
	 */
	public function __construct(string $channel, string $key = '')
	{
		$this->setChannel($channel);
		$this->setKey($key);
	}

	/**
	 * @return string
	 */
	public function getChannel(): string
	{
		return $this->channel;
	}

	/**
	 * @param string $channel
	 */
	public function setChannel(string $channel)
	{
		$this->channel = $channel;
	}

	public function formatMessage(): string
	{
		$key = $this->getKey();

		return 'JOIN ' . $this->getChannel() . (!empty($key) ? ' :' . $key : '') . "\r\n";
	}
}