<?php
namespace DiscordBot;
class Role
{
	const PERMISSION_CREATE_INSTANT_INVITE = 0x00000001;
	const PERMISSION_KICK_MEMBERS = 0x00000002;
	const PERMISSION_BAN_MEMBERS = 0x00000004;
	const PERMISSION_ADMINISTRATOR = 0x00000008;
	const PERMISSION_MANAGE_CHANNELS = 0x00000010;
	const PERMISSION_MANAGE_GUILD = 0x00000020;
	const PERMISSION_ADD_REACTIONS = 0x00000040;
	const PERMISSION_VIEW_AUDIT_LOG = 0x00000080;
	const PERMISSION_VIEW_CHANNEL = 0x00000400;
	const PERMISSION_SEND_MESSAGES = 0x00000800;
	const PERMISSION_SEND_TTS_MESSAGES = 0x00001000;
	const PERMISSION_MANAGE_MESSAGES = 0x00002000;
	const PERMISSION_EMBED_LINKS = 0x00004000;
	const PERMISSION_ATTACH_FILES = 0x00008000;
	const PERMISSION_READ_MESSAGE_HISTORY = 0x00010000;
	const PERMISSION_MENTION_EVERYONE = 0x00020000;
	const PERMISSION_USE_EXTERNAL_EMOJIS = 0x00040000;
	const PERMISSION_CONNECT = 0x00100000;
	const PERMISSION_SPEAK = 0x00200000;
	const PERMISSION_MUTE_MEMBERS = 0x00400000;
	const PERMISSION_DEAFEN_MEMBERS = 0x00800000;
	const PERMISSION_MOVE_MEMBERS = 0x01000000;
	const PERMISSION_USE_VAD = 0x02000000;
	const PERMISSION_PRIORITY_SPEAKER = 0x00000100;
	const PERMISSION_STREAM = 0x00000200;
	const PERMISSION_CHANGE_NICKNAME = 0x04000000;
	const PERMISSION_MANAGE_NICKNAMES = 0x08000000;
	const PERMISSION_MANAGE_ROLES = 0x10000000;
	const PERMISSION_MANAGE_WEBHOOKS = 0x20000000;
	const PERMISSION_MANAGE_EMOJIS = 0x40000000;
	const PERMISSION_ALL = 0xFFFFFFFF;
	/**
	 * @var Guild $guild
	 */
	public $guild;
	public $id;
	public $name;
	/**
	 * Permissions bit flags.
	 *
	 * @var int $permissions
	 */
	public $permissions;
	/**
	 * Whether the role is displayed separately in the sidebar.
	 *
	 * @var bool $hoist
	 */
	public $hoist;
	/**
	 * @var bool $mentionable
	 */
	public $mentionable;

	function __construct(Guild &$guild, array $data)
	{
		$this->guild = $guild;
		$this->id = $data["id"];
		$this->name = $data["name"];
		$this->permissions = $data["permissions"];
		$this->hoist = $data["hoist"];
		$this->mentionable = $data["mentionable"];
	}

	/**
	 * @param string $name
	 * @return Role $this
	 */
	function setName(string $name): Role
	{
		if($name != $this->name)
		{
			$this->guild->discord->http("PATCH", "/guilds/{$this->guild->id}/roles/{$this->id}", [
				"name" => $name
			]);
		}
		return $this;
	}

	/**
	 * Set whether the role is displayed separately in the sidebar.
	 *
	 * @param bool $hoist
	 * @return Role $this
	 */
	function setHoist(bool $hoist): Role
	{
		if($hoist != $this->hoist)
		{
			$this->guild->discord->http("PATCH", "/guilds/{$this->guild->id}/roles/{$this->id}", [
				"hoist" => $hoist
			]);
		}
		return $this;
	}

	/**
	 * @param bool $mentionable
	 * @return Role $this
	 */
	function setMentionable(bool $mentionable): Role
	{
		if($mentionable != $this->mentionable)
		{
			$this->guild->discord->http("PATCH", "/guilds/{$this->guild->id}/roles/{$this->id}", [
				"mentionable" => $mentionable
			]);
		}
		return $this;
	}

	function hasPermission(int $permission_bit_flag): bool
	{
		return ($this->permissions & $permission_bit_flag) == $permission_bit_flag;
	}
}
