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

namespace WildPHP\Core\Users;


use WildPHP\Core\Logger\Logger;

class UserGroup
{
	/**
	 * @var User[]
	 */
	protected $members = [];

	/**
	 * @param User $user
	 * @return bool
	 */
	public function isUserInGroup(User $user): bool
	{
		return in_array($user, $this->members);
	}

	/**
	 * @param string $nickname
	 * @return bool
	 */
	public function isUserInGroupByNickname(string $nickname): bool
	{
		return array_key_exists($nickname, $this->members);
	}

	/**
	 * @param User $user
	 */
	public function addUser(User $user)
	{
		if ($this->isUserInGroup($user))
		{
			Logger::warning('Trying to add already existing user to group. Ignoring request.', [$user]);

			return;
		}

		$nickname = $user->getNickname();
		$this->members[$nickname] = $user;
	}

	/**
	 * @param User $user
	 */
	public function removeUser(User $user)
	{
		if (!$this->isUserInGroup($user))
		{
			Logger::warning('Trying to remove non-existing user from group. Ignoring request.', [$user]);

			return;
		}

		$nickname = $user->getNickname();
		unset($this->members[$nickname]);
	}

	/**
	 * @param string $nickname
	 * @return bool|User
	 */
	public function findUserByNickname(string $nickname)
	{
		if (!$this->isUserInGroupByNickname($nickname))
			return false;

		return $this->members[$nickname];
	}

	/**
	 * @return User[]
	 */
	public function getAllUsers()
	{
		return $this->members;
	}
}