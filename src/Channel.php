<?php
namespace DiscordBot;
use InvalidArgumentException;
class Channel
{
	const TYPE_GUILD_TEXT = 0;
	const TYPE_DM = 1;
	const TYPE_GUILD_VOICE = 2;
	const TYPE_GROUP_DM = 3;
	const TYPE_GUILD_CATEGORY = 4;
	const TYPE_GUILD_NEWS = 5;
	const TYPE_GUILD_STORE = 6;
	/**
	 * @var Guild $guild
	 */
	public $guild;
	/**
	 * @var string $id
	 */
	public $id;
	/**
	 * @var int $type
	 */
	public $type;
	/**
	 * @var string $name
	 */
	public $name;
	/**
	 * @var array $permission_overwrites
	 */
	public $permission_overwrites;

	function __construct(Guild &$guild, array $data)
	{
		$this->guild = $guild;
		$this->id = $data["id"];
		$this->type = $data["type"];
		$this->name = $data["name"];
		$this->permission_overwrites = $data["permission_overwrites"];
	}

	/**
	 * Returns the string needed to mention this channel.
	 *
	 * @return string
	 */
	function getMention(): string
	{
		return "<#".$this->id.">";
	}

	function indicateTyping(): void
	{
		$this->guild->discord->http("POST", "/channels/{$this->id}/typing");
	}

	/**
	 * Asynchronously sends a message to the channel.
	 *
	 * @param string $content
	 * @param null|callable $callback
	 * @return Channel $this
	 */
	function sendMessage(string $content, ?callable $callback = null): Channel
	{
		$this->guild->discord->http("POST", "/channels/{$this->id}/messages", [
			"content" => $content
		], function($data) use ($callback)
		{
			if($callback !== null)
			{
				$callback(new Message($this, $data));
			}
		});
		return $this;
	}

	/**
	 * Asynchronously gets a message by its ID.
	 *
	 * @param string $id
	 * @param callable $callback
	 * @return Channel $this
	 */
	function getMessage(string $id, callable $callback): Channel
	{
		$this->guild->discord->http("GET", "/channels/{$this->id}/messages/{$id}", null, function($data) use ($callback)
		{
			$callback(new Message($this, $data));
		});
		return $this;
	}

	/**
	 * @param int $limit An integer between 1 and 100.
	 * @param callable $callback
	 * @return void
	 */
	function getRecentMessages(int $limit, callable $callback): void
	{
		if($limit < 1 || $limit > 100)
		{
			throw new InvalidArgumentException("\$limit must be an integer between 1 and 100");
		}
		$this->guild->discord->http("GET", "/channels/{$this->id}/messages?limit=".$limit, null, function($data) use ($callback)
		{
			$arr = [];
			foreach($data as $msg_data)
			{
				array_push($arr, new Message($this, $msg_data));
			}
			$callback($arr);
		});
	}

	/**
	 * @param Message $message The message that resulting messages should precede.
	 * @param int $limit An integer between 1 and 100.
	 * @param callable $callback
	 * @return void
	 */
	function getMessagesBefore(Message $message, int $limit, callable $callback): void
	{
		if($limit < 1 || $limit > 100)
		{
			throw new InvalidArgumentException("\$limit must be an integer between 1 and 100");
		}
		$this->guild->discord->http("GET", "/channels/{$this->id}/messages?before=".$message->id."&limit=".$limit, null, function($data) use ($callback)
		{
			$arr = [];
			foreach($data as $msg_data)
			{
				array_push($arr, new Message($this, $msg_data));
			}
			$callback($arr);
		});
	}

	/**
	 * @param $messages Message[]
	 */
	function bulkDeleteMessages(array $messages): void
	{
		if(count($messages) == 1)
		{
			$messages[0]->delete();
			return;
		}
		$ids = [];
		foreach($messages as $message)
		{
			array_push($ids, $message->id);
		}
		$this->guild->discord->http("POST", "/channels/{$this->id}/messages/bulk-delete", [
			"messages" => $ids
		]);
	}
}
