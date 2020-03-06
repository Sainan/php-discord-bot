<?php
/**
 * Sends a message in the given channel when a user leaves the guild.
 *
 * @var Plugin $this
 */
$leave_message_channel = "system-messages";
use DiscordBot\
{Channel, Event\MemberRemoveEvent};
use hotswapp\Plugin;
$this->on(function(MemberRemoveEvent $event) use (&$leave_message_channel)
{
	$chan = $event->guild->getChannel($leave_message_channel);
	if($chan instanceof Channel)
	{
		$chan->sendMessage($event->member->getUser()
										 ->getTag()." just left us :cry:");
	}
});
