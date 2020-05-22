<?php
namespace DiscordBot;
use Asyncore\
{Asyncore, Condition};
use DiscordBot\Event\
{GuildDiscoveryEvent, MemberAddEvent, MemberRemoveEvent, MessageCreateEvent, ReactionAddEvent, ReactionRemoveEvent, VoiceChannelSwitchEvent};
use Exception;
use hotswapp\PluginManager;
use LogicException;
use WebSocket\
{Connection, ServerConnection, TextFrame};
class DiscordBot
{
	const OP_EVENT = 0;
	const OP_PING = 1;
	const OP_IDENTIFY = 2;
	const OP_STATUS_UPDATE = 3;
	const OP_VOICE_STATE_UPDATE = 4;
	const OP_RESUME = 6;
	const OP_RECONNECT = 7;
	const OP_REQUEST_GUILD_MEMBERS = 8;
	const OP_INVALID_SESSION = 9;
	const OP_HELLO = 10;
	const OP_PONG = 11;
	/**
	 * @var ServerConnection $gateway
	 */
	public $gateway;
	/**
	 * The Condition that runs until ->stop is called.
	 *
	 * @var Condition $condition
	 */
	public $condition;
	/**
	 * @var $users User[]
	 */
	public $users;
	/**
	 * @var $guilds Guild[]
	 */
	public $guilds;
	public $guild_callbacks = [];
	public $my_id;
	private $reconnect_queued = false;
	private $heartbeat_loop_registered = false;
	private $token;
	private $got_pong = true;
	private $session_id;
	private $last_seq;
	private $stop_initiated = false;

	/**
	 * Discord constructor.
	 *
	 * @param string $token
	 * @throws Exception
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	function __construct(string $token)
	{
		$this->connect();
		$this->condition = new Condition(function()
		{
			if(!$this->stop_initiated)
			{
				if(!$this->gateway instanceof ServerConnection || $this->gateway->status != Connection::STATUS_OPEN)
				{
					if(!$this->reconnect_queued)
					{
						echo "Connection closed";
						if($this->gateway instanceof ServerConnection)
						{
							echo ": Code ".$this->gateway->close_code;
						}
						echo "\n";
						Asyncore::timeout(function()
						{
							$this->connect();
						}, 5);
						$this->reconnect_queued = true;
					}
				}
				return true;
			}
			return false;
		});
		$this->token = $token;
		$token = "[REDACTED]";
		$this->condition->add(function()
		{
			if(!$this->gateway instanceof ServerConnection || $this->gateway->status != Connection::STATUS_OPEN)
			{
				return;
			}
			while(($frame = $this->gateway->readFrame(0)) !== null)
			{
				echo "GATE > ".$frame->data."\n";
				$json = json_decode($frame->data, true);
				$data = $json["d"];
				switch($json["op"])
				{
					case self::OP_EVENT:
						$this->last_seq = $json["s"];
						switch($json["t"])
						{
							case "READY":
								new User($this, $data["user"]);
								$this->my_id = $data["user"]["id"];
								$this->session_id = $data["session_id"];
								break;
							case "GUILD_CREATE":
								if(!@$data["unavailable"])
								{
									$guild = new Guild($this, $data);
									PluginManager::fire(new GuildDiscoveryEvent($guild));
									if(!empty($this->guild_callbacks[$guild->name]))
									{
										foreach($this->guild_callbacks[$guild->name] as $callback)
										{
											$callback($guild);
										}
										unset($this->guild_callbacks[$guild->name]);
									}
								}
								break;
							case "GUILD_MEMBER_ADD":
								$this->guilds[$data["guild_id"]]->members[$data["user"]["id"]] = new Member($this->guilds[$data["guild_id"]], $data);
								PluginManager::fire(new MemberAddEvent($this->guilds[$data["guild_id"]]->members[$data["user"]["id"]]));
								break;
							case "GUILD_MEMBER_UPDATE":
								$this->guilds[$data["guild_id"]]->members[$data["user"]["id"]]->update($data);
								break;
							case "GUILD_MEMBER_REMOVE":
								PluginManager::fire(new MemberRemoveEvent($this->guilds[$data["guild_id"]]->members[$data["user"]["id"]]));
								unset($this->guilds[$data["guild_id"]]->members[$data["user"]["id"]]);
								break;
							case "PRESENCE_UPDATE":
								$this->users[$data["user"]["id"]]->updatePresence($data);
								break;
							case "VOICE_STATE_UPDATE":
								$member = $this->guilds[$data["guild_id"]]->members[$data["member"]["user"]["id"]];
								$prev_channel = $member->getVoiceChannel();
								$member->voice_channel_id = $data["channel_id"];
								PluginManager::fire(new VoiceChannelSwitchEvent($member, $prev_channel));
								break;
							case "CHANNEL_CREATE":
								$this->guilds[$data["guild_id"]]->channels[$data["id"]] = new Channel($this->guilds[$data["guild_id"]], $data);
								break;
							case "CHANNEL_DELETE":
								unset($this->guilds[$data["guild_id"]]->channels[$data["id"]]);
								break;
							case "MESSAGE_CREATE":
								$channel = $this->guilds[$data["guild_id"]]->channels[$data["channel_id"]];
								$message = new Message($channel, $data);
								if(PluginManager::fire(new MessageCreateEvent($channel, $message)))
								{
									$message->delete();
								}
								break;
							case "MESSAGE_REACTION_ADD":
								$channel = $this->guilds[$data["guild_id"]]->channels[$data["channel_id"]];
								$member = $this->guilds[$data["guild_id"]]->members[$data["user_id"]];
								if(PluginManager::fire(new ReactionAddEvent($channel, $member, $data["message_id"], $data["emoji"])))
								{
									$this->http("DELETE", "/channels/{$channel->id}/messages/".$data["message_id"]."/reactions/".rawurlencode($data["emoji"]["name"])."/{$member->user_id}");
								}
								break;
							case "MESSAGE_REACTION_REMOVE":
								$channel = $this->guilds[$data["guild_id"]]->channels[$data["channel_id"]];
								$member = $this->guilds[$data["guild_id"]]->members[$data["user_id"]];
								PluginManager::fire(new ReactionRemoveEvent($channel, $member, $data["message_id"], $data["emoji"]));
								break;
							default:
								echo "Unhandled event ".$json["t"]."\n";
						}
						break;
					case self::OP_PING;
						$this->write(self::OP_PING, $this->last_seq);
						break;
					case self::OP_PONG:
						$this->got_pong = true;
						break;
					case self::OP_HELLO:
						if(!$this->heartbeat_loop_registered)
						{
							$this->condition->add(function()
							{
								if(!$this->gateway instanceof ServerConnection || $this->gateway->status != Connection::STATUS_OPEN)
								{
									return;
								}
								if($this->got_pong)
								{
									$this->write(self::OP_PING, $this->last_seq);
									$this->got_pong = false;
								}
								else
								{
									echo "Got no pong. Declaring the connection dead.\n";
									$this->gateway = null;
								}
							}, $data["heartbeat_interval"] / 1000);
							$this->heartbeat_loop_registered = true;
						}
						if($this->session_id && $this->last_seq)
						{
							$this->write(self::OP_RESUME, [
								"token" => $this->token,
								"session_id" => $this->session_id,
								"seq" => $this->last_seq
							]);
						}
						else
						{
							$this->identify();
						}
						break;
					case self::OP_INVALID_SESSION:
						Asyncore::timeout(function()
						{
							$this->identify();
						}, 5);
						break;
					default:
						echo "Unhandled opcode ".$json["op"]."\n";
				}
			}
		});
		$token = "";
		echo $token;
	}

	/**
	 * @throws Exception
	 */
	private function connect(): void
	{
		$this->gateway = new ServerConnection(json_decode(file_get_contents("https://discordapp.com/api/v6/gateway"), true)["url"]."?v=6&encoding=json");
		$this->reconnect_queued = false;
	}

	function http(string $method, string $path, ?array $data = null, ?callable $callback = null): void
	{
		$payload = ($data === null ? "" : json_encode($data));
		echo "HTTP < $method $path $payload\n";
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_URL => "https://discordapp.com/api/v6".$path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bot ".$this->token,
				"Content-Type: application/json",
				"User-Agent: hell-sh/php-discord-bot"
			]
		]);
		if($method != "GET")
		{
			curl_setopt_array($ch, [
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => ($data === null ? "" : json_encode($data))
			]);
		}
		else if($data !== null)
		{
			throw new LogicException("\$data is not NULL but \$method is GET");
		}
		if(Asyncore::isWindows())
		{
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__."/cacert.pem");
		}
		Asyncore::curl_exec($ch, function($res) use ($method, $path, &$ch, $data, $callback)
		{
			echo "HTTP > $method $path ".curl_getinfo($ch, CURLINFO_HTTP_CODE)." $res\n";
			curl_close($ch);
			$res = json_decode($res, true);
			if($res && array_key_exists("retry_after", $res))
			{
				Asyncore::timeout(function() use ($method, $path, $data, $callback)
				{
					self::http($method, $path, $data, $callback);
				}, $res["retry_after"] / 1000);
			}
			else if($callback !== null)
			{
				$callback($res);
			}
		});
	}

	function write(int $op, $data)
	{
		$json = json_encode([
			"op" => $op,
			"d" => $data
		]);
		echo "GATE < $json\n";
		$this->gateway->writeFrame(new TextFrame($json));
		$this->gateway->flush();
	}

	private function identify()
	{
		$this->write(self::OP_IDENTIFY, [
			"token" => $this->token,
			"properties" => [
				"\$os" => "TempleOS",
				"\$browser" => "Mozzarella Cheese",
				"\$device" => "iFridge"
			],
			"compress" => false,
			"large_threshold" => 250,
			"presence" => [
				"status" => "online",
				"afk" => false
			],
			"guild_subscriptions" => true
		]);
	}

	function stop(): void
	{
		$this->stop_initiated = true;
		$this->gateway->close();
	}

	function getUser(): User
	{
		return $this->users[$this->my_id];
	}

	/**
	 * @param string $name
	 * @param callable $callback
	 * @return DiscordBot $this
	 */
	function awaitGuild(string $name, callable $callback): DiscordBot
	{
		$guild = $this->getGuild($name);
		if($guild !== null)
		{
			$callback($guild);
		}
		else if(array_key_exists($name, $this->guild_callbacks))
		{
			array_push($this->guild_callbacks[$name], $callback);
		}
		else
		{
			$this->guild_callbacks[$name] = [$callback];
		}
		return $this;
	}

	function getGuild(string $name): ?Guild
	{
		foreach($this->guilds as $guild)
		{
			if($guild->name == $name)
			{
				return $guild;
			}
		}
		return null;
	}
}
