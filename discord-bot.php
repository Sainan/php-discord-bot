<?php /** @noinspection PhpUnhandledExceptionInspection */
if(!is_file(__DIR__."/vendor/autoload.php"))
{
	die("Couldn't find autoload script. Execute `composer install` and try again.\n");
}
$token = is_file("token.txt") ? trim(file_get_contents("token.txt")) : "";
if(!$token)
{
	die("Couldn't find token. Please follow the instructions given in README.md.\n");
}
require __DIR__."/vendor/autoload.php";
use Asyncore\
{Asyncore, stdin};
use DiscordBot\DiscordBot;
use hotswapp\PluginManager;
$discord = new DiscordBot($token);
PluginManager::loadPlugins();
stdin::init(function($line)
{
	if($line == "reload")
	{
		PluginManager::unloadAllPlugins();
		PluginManager::loadPlugins();
		echo "Reloaded plugins.\n";
	}
}, false);
Asyncore::loop();
