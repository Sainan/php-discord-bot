<?php
/**
 * Gives users a prefix for the game they're currently playing.
 *
 * @var Plugin $this
 */
$guild_name = "One Hug";
$game_abbreviations = [
	"Grand Theft Auto V" => "GTA",
	"Warframe" => "WF"
	/*"Archeage" => "AA",
	"Counter-Strike: Global Offensive" => "CSGO",
	"Elite Dangerous" => "E",
	"Fortnite" => "F",
	"Minecraft" => "MC",
	"Rainbow Six Siege" => "R6",
	"Tom Clancy's Rainbow Six Siege" => "R6",
	"Red Dead Redemption 2" => "RDR",
	"Wolcen: Lords of Mayhem " => "WLOM"*/
];
use DiscordBot\
{Event\PresenceUpdateActivitiesEvent, Guild, Member};
use hotswapp\Plugin;
$this->on(function(PresenceUpdateActivitiesEvent $event) use (&$guild_name, &$game_abbreviations)
{
	// Guild may not have been discovered yet
	$event->discord->awaitGuild($guild_name, function(Guild $guild) use ($event, &$game_abbreviations)
	{
		$member = $guild->getMember($event->presence);
		if(!$member instanceof Member)
		{
			return;
		}
		$prefix = "";
		foreach($event->presence->activities as $activity)
		{
			if($activity["type"] == 0 && array_key_exists($activity["name"], $game_abbreviations))
			{
				$prefix = "[".$game_abbreviations[$activity["name"]]."] ";
				break;
			}
		}
		$nick = $member->getNickname();
		if($i = strpos($nick, "] "))
		{
			$nick = substr($nick, $i + 2);
		}
		$member->setNickname($prefix.$nick);
	});
});
