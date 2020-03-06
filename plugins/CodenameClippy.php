<?php
/**
 * If users @-mention this bot, it will post the message to Codename: Clippy and respond accordingly.
 * Learn more at https://playground.timmyrs.de/codename-clippy/
 *
 * @var Plugin $this
 */
use Asyncore\Asyncore;
use DiscordBot\
{Event\MessageCreateEvent, Member, Role};
use hotswapp\
{Event, Plugin};
$this->on(function(MessageCreateEvent $event)
{
	if($event->message->mentionsMe() && $event->message->getAuthor() instanceof Member)
	{
		$event->channel->indicateTyping();
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => "https://playground.timmyrs.de/codename-clippy/talk",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => "message=".rawurlencode($event->message->getContent(true))
		]);
		if(Asyncore::isWindows())
		{
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__."/../src/cacert.pem");
		}
		Asyncore::curl_exec($ch, function($data) use (&$event)
		{
			$data = json_decode($data, true);
			switch($data["action"]["type"])
			{
				case "delete":
					if(array_key_exists("amount", $data["action"]))
					{
						if(!$event->message->getAuthor()
										   ->hasPermission(Role::PERMISSION_MANAGE_MESSAGES, $event->message->channel))
						{
							$event->channel->sendMessage($event->message->getAuthor()
																		->getMention()." ".$data["responses"]["no_permission"]);
							break;
						}
						if($data["action"]["amount"] > 99)
						{
							$event->channel->sendMessage($event->message->getAuthor()
																		->getMention()." ".str_replace("%amount%", "99", $data["responses"]["limit"]));
							break;
						}
						$event->channel->getMessagesBefore($event->message, $data["action"]["amount"], function($messages) use (&$event)
						{
							array_push($messages, $event->message);
							$event->channel->bulkDeleteMessages($messages);
						});
					}
					else
					{
						$event->channel->sendMessage($event->message->getAuthor()
																	->getMention()." ".$data["responses"]["default"]);
					}
					break;
				default:
					$event->channel->sendMessage($event->message->getAuthor()
																->getMention()." ".$data["responses"]["default"]);
			}
		});
	}
}, Event::PRIORITY_LOWEST);
