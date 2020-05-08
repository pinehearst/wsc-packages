<?php
namespace wcf\data\pinehearst\registry;

use ArgumentCountError;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

trait TDatabaseObjectBy {
	public static function by($by): self {
		if (empty($by) || is_object($by)) {
			throw new ArgumentCountError();
		}

		if (!is_array($by)) {
			$by = [static::getDatabaseTableIndexName() => $by];
		}

		$conditions = new PreparedStatementConditionBuilder();

		foreach ($by as $key => $val) {
			$conditions->add(sprintf('%s = ?', $key), [$val]);
		}

		$sql = sprintf(
			'SELECT * FROM %s %s',
			static::getDatabaseTableName(),
			$conditions
		);

		$stmt = WCF::getDB()->prepareStatement($sql);
		$stmt->execute(array_values($by));
		$row = $stmt->fetchArray();
		if (empty($row)) {
			$row = [];
		}
		return new static(null, $row);
	}
}
