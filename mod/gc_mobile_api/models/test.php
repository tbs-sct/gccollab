<?php

elgg_ws_expose_function(
	"get.grouptest",
	"get_grouptest",
	array(
		"user" => array('type' => 'string', 'required' => true),
		"guid" => array('type' => 'int', 'required' => true),
		"lang" => array('type' => 'string', 'required' => false, 'default' => "en")
	),
	'Retrieves a group based on user id and group id',
	'POST',
	true,
	false
);

function get_grouptest($user, $guid, $lang)
{
	$user_entity = is_numeric($user) ? get_user($user) : (strpos($user, '@') !== false ? get_user_by_email($user)[0] : get_user_by_username($user));
	if (!$user_entity) {
		return "User was not found. Please try a different GUID, username, or email address";
	}
	if (!$user_entity instanceof ElggUser) {
		return "Invalid user. Please try a different GUID, username, or email address";
	}

	$ia = elgg_set_ignore_access(true);
	$entity = get_entity($guid);
	elgg_set_ignore_access($ia);

	if (!$entity) {
		return "Group was not found. Please try a different GUID";
	}
	if (!$entity instanceof ElggGroup) {
		return "Invalid group. Please try a different GUID";
	}

	if (!elgg_is_logged_in()) {
		login($user_entity);
	}

	$groups = elgg_list_entities(array(
		'type' => 'group',
		'guid' => $guid
	));
	$group = json_decode($groups)[0];

	$group->name = gc_explode_translation($group->name, $lang);

	$likes = elgg_get_annotations(array(
		'guid' => $group->guid,
		'annotation_name' => 'likes'
	));
	$group->likes = count($likes);

	$liked = elgg_get_annotations(array(
		'guid' => $group->guid,
		'annotation_owner_guid' => $user_entity->guid,
		'annotation_name' => 'likes'
	));
	$group->liked = count($liked) > 0;

	$groupObj = get_entity($group->guid);
	$group->public = $groupObj->isPublicMembership();
	$group->member = $groupObj->isMember($user_entity);
	if (!$group->public && !$group->member){
		$group->access = false;
	} else {
		$group->access = true;
	}

	$group->owner = ($groupObj->getOwnerEntity() == $user_entity);
	$group->iconURL = $groupObj->geticon();
	$group->count = $groupObj->getMembers(array('count' => true));
	$group->tags = $groupObj->interests;
	$group->userDetails = get_user_block($group->owner_guid, $lang);

	if ($group->access){
		$group->description = gc_explode_translation($group->description, $lang);
	} else {
		$group->description = elgg_echo("groups:access:private", $lang);
	}



	return $group;
}
