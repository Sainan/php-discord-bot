<?php
/**
 * Disconnects people who enter the AFK channel.
 *
 * @var Plugin $this
 */
use DiscordBot\
{Channel, Event\VoiceChannelSwitchEvent};
use hotswapp\Plugin;
$this->on(function(VoiceChannelSwitchEvent $event)
{
	$chan = $event->member->getVoiceChannel();
	if($chan instanceof Channel && $chan->id === $event->member->guild->afk_channel_id)
	{
		$event->member->disconnectFromVoice();
	}
});
