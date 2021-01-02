<?php
/**
 * If users @-mention this bot, it will invoke Clippy <https://github.com/Sainan/Clippy> to respond and/or react.
 *
 * @var Plugin $this
 */
use Clippy\
{Command, CommandDelete};
use DiscordBot\
{Event\MessageCreateEvent, Member, Role};
use hotswapp\
{Event, Plugin};
$this->on(function(MessageCreateEvent $event)
{
	if($event->message->mentionsMe() && $event->message->getAuthor() instanceof Member)
	{
		$event->channel->indicateTyping();
		$command = Command::match($event->message->getContent(true));
		switch($command::class)
		{
			case CommandDelete::class:
				if(!$event->message->getAuthor()
								   ->hasPermission(Role::PERMISSION_MANAGE_MESSAGES, $event->message->channel))
				{
					$event->channel->sendMessage($event->message->getAuthor()->getMention()." Why don't you do it yourself? ;)");
					break;
				}
				if($command->amount > 99)
				{
					$event->channel->sendMessage($event->message->getAuthor()
																->getMention()." I'm sorry, I can't delete more than 99 messages at once. :|");
					break;
				}
				$event->channel->getMessagesBefore($event->message, $command->amount, function($messages) use (&$event)
				{
					array_push($messages, $event->message);
					$event->channel->bulkDeleteMessages($messages);
				});
				break;

			default:
				$event->channel->sendMessage($event->message->getAuthor()->getMention()." ".$command->getDefaultResponse());
		}
	}
}, Event::PRIORITY_LOWEST);
