<?php
namespace wcf\data\pinehearst\registry;

use wcf\data\DatabaseObject;
use wcf\data\user\option\ViewableUserOption;
use wcf\data\user\User;

final class Location extends DatabaseObject {
	use TDatabaseObjectBy;

	public const USER_OPTION_NAME = 'pinehearstRegistryLocation';

	protected static $databaseTableName = 'pinehearst_registry_location';

	public static function getUserOptionName () {
		return self::USER_OPTION_NAME;
	}

	public static function getUserOption(): ViewableUserOption {
		return ViewableUserOption::getUserOption(self::USER_OPTION_NAME);
	}

	public static function getUserOptionID(): int {
		return self::getUserOption()->optionID;
	}

	public static function byUser(User $user): self {
		return self::by([
			'locationName' => $user->getUserOption(self::USER_OPTION_NAME)
		]);
	}
}
