<?php
namespace DiscordBot\Event;
use DiscordBot\Guild;
abstract class GuildEvent extends Event
{
	/**
	 * @var Guild $guild
	 */
	public $guild;

	function __construct(Guild &$guild)
	{
		parent::__construct($guild->discord);
		$this->guild = $guild;
	}
}
