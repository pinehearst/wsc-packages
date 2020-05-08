<?php
namespace wcf\data\pinehearst\registry;

use wcf\data\DatabaseObject;

final class Entry extends DatabaseObject {
	use TDatabaseObjectBy;

	protected static $databaseTableName = 'pinehearst_registry_entry';
}
