<?php
namespace DiscordBot\Event;
use DiscordBot\Member;
class MemberAddEvent extends GuildEvent
{
	/**
	 * @var Member
	 */
	public $member;

	function __construct(Member &$member)
	{
		parent::__construct($member->guild);
		$this->member = $member;
	}
}
