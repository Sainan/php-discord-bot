<?php
/**
 * @var Plugin $this
 */
use DiscordBot\Event\MessageCreateEvent;
use hotswapp\Plugin;
$this->on(function(MessageCreateEvent $event)
{
	if($event->message->channel->name == "votes")
	{
		$event->message->addReaction("👍", function() use (&$event)
		{
			$event->message->addReaction("👎");
		});
	}
});
