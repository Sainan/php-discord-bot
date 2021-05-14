<?php
/**
 * If users @-mention this bot with a message in the "hackban <user_id>" format, it will ban the user with the given ID, even if they're not in the guild.
 *
 * @var Plugin $this
 */
use DiscordBot\
{Event\MessageCreateEvent, Member, Role};
use hotswapp\Plugin;
$this->on(function(MessageCreateEvent $event)
{
	if($event->message->mentionsMe() && $event->message->getAuthor() instanceof Member)
	{
		$cont = $event->message->getContent(true);
		if(substr($cont, 0, 8) == "hackban ")
		{
			if($event->message->getAuthor()->hasPermission(Role::PERMISSION_BAN_MEMBERS))
			{
				$event->guild->addBan(substr($cont, 8));
				$event->channel->sendMessage($event->message->getAuthor()->getMention()." Request sent. :)");
			}
			else
			{
				$event->channel->sendMessage($event->message->getAuthor()->getMention()." Why don't you do it yourself? ;)");
			}
			$event->handled = true;
		}
	}
});
