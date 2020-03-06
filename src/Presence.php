<?php
namespace DiscordBot;
use DiscordBot\Event\PresenceUpdateActivitiesEvent;
use hotswapp\PluginManager;
class Presence
{
	public $discord;
	public $user_id;
	public $activities = null;

	function __construct(DiscordBot &$discord, array $data)
	{
		$this->discord = $discord;
		$this->user_id = $data["user"]["id"];
		$this->update($data);
	}

	function update(array $data): void
	{
		if($data["activities"] !== $this->activities)
		{
			$prev_activities = $this->activities;
			$this->activities = $data["activities"];
			PluginManager::fire(new PresenceUpdateActivitiesEvent($this, $prev_activities));
		}
	}

	function getUser(): User
	{
		return $this->discord->users[$this->user_id];
	}
}
