<?php
namespace wcf\system\event\listener\pinehearst\registry;

use wcf\data\pinehearst\registry\Entry;
use wcf\data\pinehearst\registry\EntryAction;
use wcf\data\pinehearst\registry\Location;
use wcf\data\user\User;
use wcf\system\event\listener\IParameterizedEventListener;

class RelocationListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	// listens to wcf\data\user\UserAction->initializeAction
	// TODO: Create a Listener that fires a more dedicated Event?
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$params = $eventObj->getParameters();

		if (empty($params['options'] ?? null)) {
			return;
		}

		$locationName = $params['options'][Location::getUserOptionID()] ?? null;

		if (empty($locationName)) {
			return;
		}

		$to = Location::by(['locationName' => $locationName]);

		// could be a bulk change I guess :shrug:
		foreach ($eventObj->getObjectIDs() as $userID) {
			$user = new User($userID);
			$entry = Entry::by(['userID' => $userID]);

			$action = new EntryAction([$entry], EntryAction::REQUEST_RELOCATE, [
				'user' => $user,
				'from' => Location::byUser($user),
				'to' => $to,
			]);
			$action->validateAction();
			$action->executeAction();
		}
	}
}
