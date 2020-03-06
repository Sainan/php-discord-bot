<?php
namespace DiscordBot\Event;
use DiscordBot\
{Channel, Member};
class ReactionRemoveEvent extends GuildEvent
{
	/**
	 * @var Channel $channel
	 */
	public $channel;
	/**
	 * @var Member $member
	 */
	public $member;
	/**
	 * @var string $message_id
	 */
	public $message_id;
	/**
	 * @var array $emoji
	 */
	public $emoji;

	function __construct(Channel &$channel, Member &$member, string $message_id, array $emoji)
	{
		parent::__construct($channel->guild);
		$this->channel = $channel;
		$this->member = $member;
		$this->message_id = $message_id;
		$this->emoji = $emoji;
	}

	function getMessage(callable $callback): void
	{
		$this->channel->getMessage($this->message_id, $callback);
	}
}
