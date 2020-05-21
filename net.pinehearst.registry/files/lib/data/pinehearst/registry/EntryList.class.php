<?php
namespace wcf\data\pinehearst\registry;

use wcf\data\DatabaseObjectList;

class EntryList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Entry::class;

	public $sqlSelects = '
		COALESCE(p.time, registeredOn) AS lastActivity,
		t.threadID AS threadID,
		u.username,
		l.boardID AS locationBoardID,
		l.groupID AS locationGroupID,
		l.locationName AS locationName
	';

	public $sqlJoins = '
		JOIN wcf1_pinehearst_registry_location l USING (locationID)
		JOIN wcf1_user u USING (userID)
		LEFT JOIN wbb1_post p USING (postID)
		LEFT JOIN wbb1_thread t USING (threadID)
	';

	public $sqlOrderBy = 'u.username';

	public static function get(): self {
		$list = new self();
		$list->readObjects();
		return $list;
	}

	public static function getParents(): self {
		$list = new self();
		$list->getConditionBuilder()->add('parentID IS NULL');
		$list->readObjects();
		return $list;
	}

	public static function getChildren($parentID = null): self {
		$list = new self();
		if (null === $parentID) {
			$list->getConditionBuilder()->add('parentID IS NOT NULL');
		}
		else {
			$list->getConditionBuilder()->add('parentID = ?', [$parentID]);
		}
		$list->readObjects();
		return $list;
	}
}
