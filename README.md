# PHP Discord Bot

Easily write your own Discord bot in PHP.

## Setup

1. Clone
2. `composer install`
3. Register an application on [Discord's Developer Portal](https://discord.com/developers/applications/).
4. Select "Bot" and click "Add Bot."
5. Reveal the token, and copy it.
6. Create a `token.txt` and paste it there.
7. `php discord-bot.php`

To get your bot to join your guild, use `https://discord.com/oauth2/authorize?scope=bot&permissions=8&client_id=CLIENT_ID` where `CLIENT_ID` should be replaced by your app's client ID, which you can find in the "General Information" section.

Finally, you can get to hacking in the `plugins/` folder!
