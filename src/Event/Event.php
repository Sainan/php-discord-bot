<?php
namespace DiscordBot\Event;
use DiscordBot\DiscordBot;
use hotswapp\Event as HotswappEvent;
abstract class Event extends HotswappEvent
{
	/**
	 * @var DiscordBot $discord
	 */
	public $discord;

	function __construct(DiscordBot &$discord)
	{
		$this->discord = $discord;
	}
}
