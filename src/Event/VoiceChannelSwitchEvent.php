<?php
namespace DiscordBot\Event;
use DiscordBot\
{Channel, Member};
class VoiceChannelSwitchEvent extends GuildEvent
{
	/**
	 * @var Member $member
	 */
	public $member;
	/**
	 * @var Channel|null $prev_channel
	 */
	public $prev_channel;

	function __construct(Member &$member, ?Channel $prev_channel)
	{
		parent::__construct($member->guild);
		$this->member = $member;
		$this->prev_channel = $prev_channel;
	}
}
