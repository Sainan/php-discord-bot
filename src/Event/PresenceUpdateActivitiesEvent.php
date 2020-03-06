<?php
namespace DiscordBot\Event;
use DiscordBot\Presence;
class PresenceUpdateActivitiesEvent extends Event
{
	/**
	 * @var Presence $presence
	 */
	public $presence;
	/**
	 * @var array|null $prev_activities
	 */
	public $prev_activities;

	function __construct(Presence &$presence, ?array $prev_activities)
	{
		parent::__construct($presence->discord);
		$this->presence = $presence;
		$this->prev_activities = $prev_activities;
	}
}
