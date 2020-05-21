<?php
namespace wcf\system\cronjob\pinehearst\registry;

use DateInterval;
use DateTime;
use wcf\data\cronjob\Cronjob;
use wbb\data\post\Post;
use wcf\data\pinehearst\registry\EntryAction;
use wcf\data\pinehearst\registry\EntryList;
use wcf\system\cronjob\AbstractCronjob;
use wcf\util\pinehearst\registry\PostUtil;

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

				// TODO: change this back to use stored post
				$postID = PostUtil::getLatestRelevantPostID($entry);
				if ($postID > 0) {
					$lastActivity = new DateTime('@' . (new Post($postID))->time);
				}

				return $lastActivity->add($threshold)->getTimestamp() < TIME_NOW;
			}
		);

		$action = new EntryAction($entries, 'loseRegistration', []);
		$action->executeAction();
	}
}
