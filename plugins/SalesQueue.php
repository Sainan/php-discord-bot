<?php
/**
 * Provides a message users can react to to be added to a queue to sell their product in GTA Online.
 *
 * @var Plugin $this
 */
$sales_queue_channel = "sales-queue";
use DiscordBot\
{Channel, Event\GuildDiscoveryEvent, Event\ReactionAddEvent, Event\ReactionRemoveEvent, Message};
use hotswapp\Plugin;
$sales_queue_message_id = is_file(".sales_queue_message_id.txt") ? trim(file_get_contents(".sales_queue_message_id.txt")) : "";
$sales_queue_message = null;
$this->on(function(GuildDiscoveryEvent $event) use (&$sales_queue_channel, &$sales_queue_message_id, &$sales_queue_message)
{
	$chan = $event->guild->getChannel($sales_queue_channel);
	if($chan instanceof Channel)
	{
		if($sales_queue_message_id)
		{
			$chan->getMessage($sales_queue_message_id, function(Message $message) use (&$sales_queue_message)
			{
				$sales_queue_message = $message;
			});
		}
		else
		{
			$chan->sendMessage("Click the :moneybag: below this message to be added to the queue!", function(Message $message) use (&$sales_queue_message_id, &$sales_queue_message)
				{
					$message->addReaction("💰");
					$sales_queue_message = $message;
					$sales_queue_message_id = $sales_queue_message->id;
					file_put_contents(".sales_queue_message_id.txt", $sales_queue_message_id);
				});
		}
	}
});
global $sales_queue;
if(empty($sales_queue))
{
	$sales_queue = [];
}
$updateSalesQueueMessage = function(array &$sales_queue, Message &$sales_queue_message)
{
	$content = "Click the :moneybag: below this message to be added to the queue!\n";
	foreach($sales_queue as $i => $user)
	{
		if($i == 9 && count($sales_queue) > 10)
		{
			$content .= "\n... and ".(count($sales_queue) - $i)." more.";
		}
		$content .= "\n".($i + 1).". ".$user->getName();
	}
	$sales_queue_message->setContent($content);
};
$this->on(function(ReactionAddEvent $event) use ($updateSalesQueueMessage, &$sales_queue_message_id, &$sales_queue_message)
{
	if($event->emoji["name"] == "💰" && $event->message_id == $sales_queue_message_id && !$event->member->isMe())
	{
		global $sales_queue;
		array_push($sales_queue, $event->member->getUser());
		$updateSalesQueueMessage($sales_queue, $sales_queue_message);
	}
});
$this->on(function(ReactionRemoveEvent $event) use ($updateSalesQueueMessage, &$sales_queue_message_id, &$sales_queue_message)
{
	if($event->emoji["name"] == "💰" && $event->message_id == $sales_queue_message_id)
	{
		global $sales_queue;
		unset($sales_queue[array_search($event->member->getUser(), $sales_queue)]);
		$sales_queue = array_values($sales_queue);
		$updateSalesQueueMessage($sales_queue, $sales_queue_message);
	}
});
