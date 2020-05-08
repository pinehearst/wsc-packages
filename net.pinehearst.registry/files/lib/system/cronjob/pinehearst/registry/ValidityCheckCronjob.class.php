<?php
namespace wcf\system\cronjob\pinehearst\registry;

use DateInterval;
use DateTime;
use wcf\data\cronjob\Cronjob;
use wcf\data\pinehearst\registry\EntryAction;
use wcf\data\pinehearst\registry\EntryList;
use wcf\system\cronjob\AbstractCronjob;
use wcf\util\pinehearst\RegistryUtil;

/**
 * Checks if registered users have lost their registration
 */
class ValidityCheckCronjob extends AbstractCronjob {
	/**
	 * @inheritDoc
	 */
	// CRON */5 * * * *
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);

		if (PINEHEARST_REGISTRY_VALIDITY_THRESHOLD_TIMESPAN < 0) {
			return;
		}

		$threshold = DateInterval::createFromDateString(
			PINEHEARST_REGISTRY_VALIDITY_THRESHOLD_TIMESPAN
		);

		$entries = array_filter(
			EntryList::get()->getObjects(),
			function ($entry) use ($threshold) {
				$lastActivity = new DateTime('@' . $entry->lastActivity);
				return $lastActivity->add($threshold)->getTimestamp() < TIME_NOW;
			}
		);

		$action = new EntryAction($entries, 'loseRegistration', []);
		$action->executeAction();
	}
}
