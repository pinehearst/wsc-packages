<?php
namespace wcf\util\pinehearst\registry;

use wcf\data\user\UserAction;
use wcf\util\pinehearst\RegistryUtil;

final class UserGroupUtil {
	private static function add(array $userIDs, int $groupID) {
		if ($groupID < 1) {
			return;
		}

		$action = new UserAction($userIDs, 'addToGroups', [
			'groups' => [$groupID],
			'deleteOldGroups' => false,
			'addDefaultGroups' => false,
		]);
		return $action->executeAction();
	}

	private static function remove(array $userIDs, int $groupID) {
		if ($groupID < 1) {
			return;
		}

		$action = new UserAction($userIDs, 'removeFromGroups', [
			'groups' => [$groupID],
		]);
		return $action->executeAction();
	}

	public static function addToParentsGroup(array $userIDs) {
		return self::add($userIDs, PINEHEARST_REGISTRY_PARENTS_GROUP_ID);
	}

	public static function removeFromParentsGroup(array $userIDs) {
		return self::remove($userIDs, PINEHEARST_REGISTRY_PARENTS_GROUP_ID);
	}

	public static function addToChildrenGroup(array $userIDs) {
		return self::add($userIDs, PINEHEARST_REGISTRY_CHILDREN_GROUP_ID);
	}

	public static function removeFromChildrenGroup(array $userIDs) {
		return self::remove($userIDs, PINEHEARST_REGISTRY_CHILDREN_GROUP_ID);
	}

	public static function addToLocationGroup(array $userIDs, int $groupID) {
		if (!PINEHEARST_REGISTRY_USE_LOCATION_GROUPS) {
			return;
		}

		return self::add($userIDs, $groupID);
	}

	public static function removeFromLocationGroup(array $userIDs, int $groupID) {
		if (!PINEHEARST_REGISTRY_USE_LOCATION_GROUPS) {
			return;
		}

		return self::remove($userIDs, $groupID);
	}
}
