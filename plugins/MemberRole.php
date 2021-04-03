<?php
/**
 * @var Plugin $this
 * @noinspection PhpUndefinedFieldInspection
 */
use hotswapp\Plugin;
use DiscordBot\
{Event\MemberAddEvent, Event\MemberUpdateEvent, Role};
$this->member_role_blacklist = is_file("MemberRole Blacklist.json") ? json_decode(file_get_contents("MemberRole Blacklist.json"), true) : [];
$this->on(function(MemberAddEvent $event)
{
	if(!array_key_exists($event->member->user_id, $this->member_role_blacklist))
	{
		$role = $event->guild->getRole("Member");
		if($role instanceof Role)
		{
			$event->member->addRole($role, true);
		}
	}
});
$this->on(function(MemberUpdateEvent $event)
{
	$role = $event->guild->getRole("Member");
	if($role instanceof Role)
	{
		$updated = false;
		if(array_key_exists($event->member->user_id, $this->member_role_blacklist))
		{
			if($event->member->hasRole($role))
			{
				unset($this->member_role_blacklist[$event->member->user_id]);
				$updated = true;
			}
		}
		else
		{
			if(!$event->member->hasRole($role))
			{
				$this->member_role_blacklist[$event->member->user_id] = true;
				$updated = true;
			}
		}
		if($updated)
		{
			file_put_contents("MemberRole Blacklist.json", json_encode($this->member_role_blacklist));
		}
	}
});
