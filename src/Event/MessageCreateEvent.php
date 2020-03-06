<?php
namespace DiscordBot\Event;
use DiscordBot\
{Channel, Message};
use hotswapp\CancellableEvent;
class MessageCreateEvent extends GuildEvent
{
	use CancellableEvent;
	/**
	 * @var Channel $channel
	 */
	public $channel;
	/**
	 * @var Message $message
	 */
	public $message;

	function __construct(Channel &$channel, Message $message)
	{
		parent::__construct($channel->guild);
		$this->channel = $channel;
		$this->message = $message;
	}
}
