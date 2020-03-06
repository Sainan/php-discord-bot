<?php
namespace DiscordBot;
class User
{
	public $discord;
	public $id;
	public $username;
	public $discriminator;
	public $bot;
	/**
	 * @var Presence|null $presence
	 */
	public $presence;

	function __construct(DiscordBot &$discord, array $data)
	{
		$this->discord = $discord;
		$this->id = $data["id"];
		$this->username = $data["username"];
		$this->discriminator = $data["discriminator"];
		$this->bot = $data["bot"] ?? false;
		$discord->users[$this->id] = $this;
	}

	/**
	 * Returns the user's username, e.g. "JamesBond"
	 *
	 * @return string
	 */
	function getName(): string
	{
		return $this->username;
	}

	/**
	 * Returns the user's tag, e.g. "JamesBond#0007"
	 *
	 * @return string
	 */
	function getTag(): string
	{
		return $this->username."#".$this->discriminator;
	}

	/**
	 * Returns the string needed to mention this user.
	 *
	 * @return string
	 */
	function getMention(): string
	{
		return "<@".$this->id.">";
	}

	function isBot(): bool
	{
		return $this->bot;
	}

	function getPresence(): ?Presence
	{
		return $this->presence;
	}

	function updatePresence(array $data)
	{
		if($this->presence instanceof Presence)
		{
			$this->presence->update($data);
		}
		else
		{
			$this->presence = new Presence($this->discord, $data);
		}
	}
}
