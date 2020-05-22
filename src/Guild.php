<?php
namespace DiscordBot;
use InvalidArgumentException;
class Guild
{
	public $discord;
	public $id;
	public $name;
	public $owner_id;
	public $afk_channel_id;
	/**
	 * @var $channels Channel[]
	 */
	public $channels = [];
	/**
	 * @var $roles Role[]
	 */
	public $roles = [];
	/**
	 * @var $members Member[]
	 */
	public $members = [];

	function __construct(DiscordBot $discord, array $data)
	{
		$this->discord = $discord;
		$this->id = $data["id"];
		$this->name = $data["name"];
		$this->owner_id = $data["owner_id"];
		$this->afk_channel_id = $data["afk_channel_id"];
		$discord->guilds[$this->id] = $this;
		foreach($data["channels"] as $channel)
		{
			$this->channels[$channel["id"]] = new Channel($this, $channel);
		}
		foreach($data["roles"] as $role)
		{
			$this->roles[$role["id"]] = new Role($this, $role);
		}
		foreach($data["members"] as $member)
		{
			$this->members[$member["user"]["id"]] = new Member($this, $member);
		}
		foreach($data["presences"] as $presence)
		{
			$discord->users[$presence["user"]["id"]]->updatePresence($presence);
		}
	}

	function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param Presence|User $user
	 * @return null|Member
	 */
	function getMember($user): ?Member
	{
		if($user instanceof Presence)
		{
			return @$this->members[$user->user_id];
		}
		if($user instanceof User)
		{
			return @$this->members[$user->id];
		}
		throw new InvalidArgumentException("Unexpected argument type for Guild::getMember");
	}

	function getRole(string $name): ?Role
	{
		if($name == "@everyone")
		{
			return $this->roles[$this->id];
		}
		foreach($this->roles as $role)
		{
			if($role->name == $name)
			{
				return $role;
			}
		}
		return null;
	}

	function getChannel(string $name): ?Channel
	{
		foreach($this->channels as $channel)
		{
			if($channel->name == $name)
			{
				return $channel;
			}
		}
		return null;
	}

	function getAfkChannel(): ?Channel
	{
		return @$this->channels[$this->afk_channel_id];
	}
}
