<?php
namespace wcf\data\pinehearst\registry;

use wcf\data\DatabaseObjectList;

class LocationList extends DatabaseObjectList {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Location::class;

	public static function get(): array {
		$list = new self();
		$list->readObjects();
		return $list->getObjects();
	}
}
