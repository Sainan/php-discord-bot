<?php
namespace DiscordBot;
use BadMethodCallException;
class Message
{
	/**
	 * @var Channel $channel
	 */
	public $channel;
	/**
	 * @var string $id
	 */
	public $id;
	/**
	 * @var string $author_id
	 */
	public $author_id;
	/**
	 * @var bool $webhook
	 */
	public $webhook;
	/**
	 * @var string $content
	 */
	public $content;

	function __construct(Channel &$channel, array $data)
	{
		$this->channel = $channel;
		$this->id = $data["id"];
		$this->author_id = $data["author"]["id"];
		$this->webhook = array_key_exists("webhook", $data["author"]);
		$this->content = $data["content"];
	}

	/**
	 * Asynchronously adds a reaction to the message.
	 *
	 * @param string $emoji The Unicode character to use for the reaction.
	 * @param null|callable $callback
	 * @return Message $this
	 */
	function addReaction(string $emoji, ?callable $callback = null): Message
	{
		$this->channel->guild->discord->http("PUT", "/channels/{$this->channel->id}/messages/{$this->id}/reactions/".rawurlencode($emoji)."/@me", null, $callback);
		return $this;
	}

	/**
	 * @param bool $remove_my_mention Remove any @-mentions of the account your bot is using.
	 * @return string
	 */
	function getContent(bool $remove_my_mention = false): string
	{
		return trim($remove_my_mention ? str_replace([
			"<@".$this->channel->guild->discord->my_id.">",
			"<@!".$this->channel->guild->discord->my_id.">"
		], [
			"",
			""
		], $this->content) : $this->content);
	}

	/**
	 * Asynchronously updates the message's content.
	 *
	 * @param string $content
	 * @return Message $this
	 */
	function setContent(string $content): Message
	{
		if($this->getAuthor()
				->getUser() !== $this->channel->guild->discord->getUser())
		{
			throw new BadMethodCallException("Can't edit messages submitted by different users");
		}
		$this->channel->guild->discord->http("PATCH", "/channels/{$this->channel->id}/messages/{$this->id}", [
			"content" => $content
		]);
		return $this;
	}

	/**
	 * Returns the message's author or null if authored by a webhook.
	 *
	 * @return Member|null
	 */
	function getAuthor(): ?Member
	{
		return $this->webhook ? null : $this->channel->guild->members[$this->author_id];
	}

	function delete(): void
	{
		$this->channel->guild->discord->http("DELETE", "/channels/{$this->channel->id}/messages/{$this->id}");
	}

	/**
	 * Returns whether the message @-mentions the account your bot is using.
	 *
	 * @return bool
	 */
	function mentionsMe(): bool
	{
		return strpos($this->content, "<@".$this->channel->guild->discord->my_id.">") !== false || strpos($this->content, "<@!".$this->channel->guild->discord->my_id.">") !== false;
	}
}
