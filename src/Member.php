<?php
namespace DiscordBot;
use Asyncore\
{Asyncore, Loop};
use DiscordBot\Event\
{MemberUpdateNickEvent, MemberUpdateRolesEvent};
use hotswapp\PluginManager;
class Member
{
	public $guild;
	public $user_id;
	public $nick;
	public $voice_channel_id;
	/**
	 * @var $roles string[]
	 */
	public $roles;
	/**
	 * @var array|null $role_update_data
	 */
	public $role_update_data;
	/**
	 * @var Loop|null $role_update_timer
	 */
	public $role_update_timer;

	function __construct(Guild &$guild, array $data)
	{
		$this->guild = $guild;
		$this->user_id = $data["user"]["id"];
		if(!array_key_exists($data["user"]["id"], $guild->discord->users))
		{
			new User($guild->discord, $data["user"]);
		}
		$this->update($data);
	}

	function update(array $data): void
	{
		if($this->nick !== @$data["nick"])
		{
			$this->nick = @$data["nick"];
			PluginManager::fire(new MemberUpdateNickEvent($this));
		}
		if($this->roles !== $data["roles"])
		{
			$this->roles = $data["roles"];
			PluginManager::fire(new MemberUpdateRolesEvent($this));
		}
	}

	function getGuild(): Guild
	{
		return $this->guild;
	}

	/**
	 * Asynchronously changes this member's nickname. Won't work on the guild's owner, unless that is the bot.
	 *
	 * @param null|string $name
	 * @return Member $this
	 */
	function setNickname(?string $name): Member
	{
		if($name !== $this->getNickname() && (!$this->isOwner() || $this->isMe()))
		{
			$this->guild->discord->http("PATCH", "/guilds/".$this->guild->id."/members/".$this->user_id, [
				"nick" => $name
			]);
		}
		return $this;
	}

	function getNickname(): string
	{
		return $this->nick ?? $this->getUser()
								   ->getName();
	}

	function getUser(): User
	{
		return $this->guild->discord->users[$this->user_id];
	}

	function isOwner(): bool
	{
		return $this->guild->owner_id === $this->user_id;
	}

	function isMe(): bool
	{
		return $this->guild->discord->my_id == $this->user_id;
	}

	function getVoiceChannel(): ?Channel
	{
		return @$this->guild->channels[$this->voice_channel_id];
	}

	function disconnectFromVoice(): void
	{
		if($this->voice_channel_id !== null)
		{
			$this->guild->discord->http("PATCH", "/guilds/{$this->guild->id}/members/{$this->user_id}", [
				"channel_id" => null
			]);
		}
	}

	/**
	 * @param Role $role
	 * @param bool $immediately Bypass some clever coding and algorithms to avoid sending to many API requests by delaying role updates to bulk them into a single request.
	 * @return Member $this
	 */
	function addRole(Role $role, bool $immediately = false): Member
	{
		if(!$this->hasRole($role))
		{
			if($immediately)
			{
				$this->guild->discord->http("PUT", "/guilds/{$this->guild->id}/members/{$this->user_id}/roles/{$role->id}");
			}
			else
			{
				$this->initRoleUpdate();
			}
			if($this->role_update_data !== null)
			{
				array_push($this->role_update_data, $role->id);
			}
		}
		return $this;
	}

	function hasRole(Role $role): bool
	{
		return $this->role_update_data !== null ? in_array($role->id, $this->role_update_data) : in_array($role->id, $this->roles);
	}

	private function initRoleUpdate(): void
	{
		if($this->role_update_data === null)
		{
			$this->role_update_data = $this->roles;
		}
		else
		{
			$this->role_update_timer->remove();
		}
		$this->role_update_timer = Asyncore::add(function()
		{
			$this->role_update_timer->remove();
			if($this->role_update_data !== null)
			{
				$this->guild->discord->http("PATCH", "/guilds/{$this->guild->id}/members/{$this->user_id}", [
					"roles" => array_values($this->role_update_data)
				]);
				$this->role_update_data = null;
			}
		}, 5);
	}

	/**
	 * @param Role $role
	 * @param bool $immediately Bypass some clever coding and algorithms to avoid sending to many API requests by delaying role updates to bulk them into a single request.
	 * @return Member $this
	 */
	function removeRole(Role $role, bool $immediately = false): Member
	{
		if($this->hasRole($role))
		{
			if($immediately)
			{
				$this->guild->discord->http("DELETE", "/guilds/{$this->guild->id}/members/{$this->user_id}/roles/{$role->id}");
			}
			else
			{
				$this->initRoleUpdate();
			}
			if($this->role_update_data !== null)
			{
				unset($this->role_update_data[array_search($role->id, $this->role_update_data)]);
			}
		}
		return $this;
	}

	/**
	 * Returns whether the member has the given permission. Optionally, for the given channel.
	 *
	 * @param int $permission_bit_flag
	 * @param Channel|null $channel
	 * @return bool
	 */
	function hasPermission(int $permission_bit_flag, ?Channel $channel = null): bool
	{
		return ($this->getPermissions($channel) & $permission_bit_flag) == $permission_bit_flag;
	}

	/**
	 * Returns the permissions of the member, optionally, for a given channel.
	 *
	 * @param Channel|null $channel
	 * @return bool
	 */
	function getPermissions(?Channel $channel = null): int
	{
		if($this->isOwner())
		{
			return Role::PERMISSION_ALL;
		}
		$permissions = 0;
		foreach($this->getRoles(true) as $role)
		{
			$permissions |= $role->permissions;
		}
		echo $permissions."\n";
		if(($permissions & Role::PERMISSION_ADMINISTRATOR) == Role::PERMISSION_ADMINISTRATOR)
		{
			return Role::PERMISSION_ALL;
		}
		if($channel instanceof Channel)
		{
			foreach($channel->permission_overwrites as $overwrite)
			{
				if($overwrite["type"] == "role" && $overwrite["id"] == $this->guild->id)
				{
					$permissions &= ($overwrite["deny"] * -1) - 1;
					$permissions |= $overwrite["allow"];
					break;
				}
			}
			echo $permissions."\n";
			$allow = 0;
			$deny = 0;
			$roles = $this->getRoles();
			foreach($channel->permission_overwrites as $overwrite)
			{
				if($overwrite["type"] == "role" && array_key_exists($overwrite["id"], $roles))
				{
					$allow |= $overwrite["allow"];
					$deny |= $overwrite["deny"];
				}
			}
			$permissions &= ($deny * -1) - 1;
			$permissions |= $allow;
			echo $permissions."\n";
			foreach($channel->permission_overwrites as $overwrite)
			{
				if($overwrite["type"] == "user" && $overwrite["id"] == $this->user_id)
				{
					$permissions &= ($overwrite["deny"] * -1) - 1;
					$permissions |= $overwrite["allow"];
					break;
				}
			}
			echo $permissions."\n";
		}
		return $permissions;
	}

	/**
	 * @param bool $include_at_everyone Whether to include the &commat;everyone rule.
	 * @return Role[]
	 */
	function getRoles(bool $include_at_everyone = false): array
	{
		$roles = [];
		if($include_at_everyone)
		{
			array_push($roles, $this->guild->roles[$this->guild->id]);
		}
		foreach(($this->role_update_data !== null ? $this->role_update_data : $this->roles) as $role_id)
		{
			array_push($roles, $this->guild->roles[$role_id]);
		}
		return $roles;
	}

	/**
	 * Returns the string needed to mention this member.
	 *
	 * @return string
	 */
	function getMention(): string
	{
		return "<@".($this->nick !== null ? "!" : "").$this->user_id.">";
	}
}
