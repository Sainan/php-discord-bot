<?php
/**
 * Gives people a hint when they are issuing bot commands in the wrong channel.
 *
 * @var Plugin $this
 */
$bot_commands_channel = "robotic-love";
use DiscordBot\
{Channel, Event\MessageCreateEvent};
use hotswapp\Plugin;
$this->on(function(MessageCreateEvent $event) use (&$bot_commands_channel)
{
	if(in_array(substr($event->message->content, 0, 1), [
			"!",
			"-"
		]) && !$event->message->mentionsMe())
	{
		$chan = $event->guild->getChannel($bot_commands_channel);
		if($chan instanceof Channel)
		{
			if($event->channel !== $chan)
			{
				$event->channel->sendMessage($event->message->getAuthor()
															->getMention()." Please try to keep your bot commands in ".$chan->getMention().". :smile:");
			}
			// Add -fuckoff command to Groovy
			if($event->message->content == "-fuckoff" && array_key_exists("234395307759108106", $event->guild->members))
			{
				$event->guild->members["234395307759108106"]->disconnectFromVoice();
			}
		}
	}
});
