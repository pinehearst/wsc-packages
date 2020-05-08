<?php
namespace wcf\data\pinehearst\registry;

use stdClass;
use wbb\data\post\Post;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\cronjob\Cronjob;
use wcf\data\user\User;
use wcf\system\cronjob\pinehearst\registry\ValidityCheckCronjob;
use wcf\system\exception\UserInputException;
use wcf\util\pinehearst\registry\PostUtil;
use wcf\util\pinehearst\registry\UserGroupUtil;
use wcf\util\pinehearst\RegistryUtil;

class EntryAction extends AbstractDatabaseObjectAction {
	public const
		REQUEST_UNKNOWN = 'requestUnknown',
		REQUEST_RELOCATE = 'requestRelocate',
		REQUEST_REGISTER = 'requestRegister',
		REQUEST_DEREGISTER = 'requestDeregister',
		REQUEST_ADD = 'requestAdd',
		REQUEST_REMOVE = 'requestRemove',
		REQUEST_PROMOTE = 'requestPromote',
		REQUEST_UPDATE = 'requestUpdate',
		REQUEST_CHECK = 'requestCheck';

	private
		$user = null,
		$post = null;

	private function approveRequest(string $message, array $messageArgs = [], EntryList $children = null) {
		PostUtil::updateProtocol();
		return PostUtil::createResponsePost([
			'approved' => true,
			'message' => vsprintf($message, $messageArgs),
			'user' => $this->user,
			'post' => $this->post,
			'children' => $children,
		]);
	}

	private function denyRequest(string $message, array $messageArgs = [], EntryList $children = null) {
		return PostUtil::createResponsePost([
			'approved' => false,
			'message' => vsprintf($message, $messageArgs),
			'user' => $this->user,
			'post' => $this->post,
			'children' => $children,
		]);
	}

	private function thresholdPost(User $user, EntryList $children) {
		return PostUtil::createResponsePost([
			'message' => sprintf(
				'You lost your registration due to being [b]inactive for more than %s[/b]',
				PINEHEARST_REGISTRY_VALIDITY_THRESHOLD_TIMESPAN
			),
			'user' => $user,
			'children' => $children,
		]);
	}

	private function getEntryEditor() {
		$objects = $this->getObjects();
		if (count($objects) != 1) {
			throw new UserInputException('objectIDs');
		}
		return $objects[0];
	}

	private function getTargetName() {
		return $this->parameters['target'] ?? null;
	}

	private function validateRequestPost() {
		if (Post::class !== get_class($this->parameters['post'] ?? new stdClass)) {
			throw new UserInputException('post');
		}
		$this->post = $this->parameters['post'];
		$this->user = new User($this->post->userID);
	}

	public function validateRequestUnknown() {
		$this->validateRequestPost();
	}

	public function requestUnknown() {
		return $this->denyRequest('Unknown Request type: [quote]%s[/quote]', [
			$this->post->getPlainTextMessage()
		]);
	}

	public function validateRequestRegister() {
		$this->validateRequestPost();
	}

	public function requestRegister() {
		$entry = $this->getEntryEditor();

		if ($entry->entryID > 0) {
			return $this->denyRequest('You are already registered.');
		}

		$location = Location::byUser($this->user);

		if ($location->locationID < 1) {
			return $this->denyRequest('You have be registered as living in a State or Territory of the United States.');
		}

		EntryEditor::create([
			'userID' => $this->user->userID,
			'locationID' => $location->locationID,
			'registeredOn' => TIME_NOW,
		]);

		UserGroupUtil::addToParentsGroup([$this->user->userID]);
		UserGroupUtil::addToLocationGroup([$this->user->userID], $location->groupID);

		return $this->approveRequest('You successfully registered as a [b]Federal-ID[/b] in [b]%s[/b]', [
			$location->locationName,
		]);
	}

	public function validateRequestDeregister() {
		$this->validateRequestPost();
	}

	public function requestDeregister() {
		$entry = $this->getEntryEditor();

		if ($entry->entryID < 1) {
			return $this->denyRequest('You are not registered.');
		}

		$children = null;

		if ($entry->parentID < 1) {
			$children = EntryList::getChildren($entry->entryID);

			foreach ($children->getObjects() as $child) {
				UserGroupUtil::removeFromChildrenGroup([$child->userID]);
				UserGroupUtil::removeFromLocationGroup([$child->userID], $child->locationGroupID);
			}
		}

		$location = new Location($entry->locationID);
		$entry->delete();

		UserGroupUtil::removeFromParentsGroup([$entry->userID]);
		UserGroupUtil::removeFromLocationGroup([$entry->userID], $location->groupID);

		return $this->approveRequest('You successfully deregistered yourself.', [], $children);
	}

	public function validateRequestRelocate() {
		foreach (['from', 'to'] as $location) {
			if (Location::class !== get_class($this->parameters[$location] ?? new stdClass)) {
				throw new UserInputException($location);
			}
		}
	}

	public function requestRelocate() {
		$entry = $this->getEntryEditor();

		if ($entry->entryID < 1) {
			return;
		}

		$to = $this->parameters['to'];
		$from = $this->parameters['from'];

		if ($to->locationID === $from->locationID) {
			return;
		}

		$this->user = new User($entry->userID);

		$this->post = PostUtil::createRelocationPost($this->user, $from, $to);

		if ($to->locationID < 1) {
			$entry->delete();

			UserGroupUtil::removeFromParentsGroup([$entry->userID]);
			UserGroupUtil::removeFromChildrenGroup([$entry->userID]);
			UserGroupUtil::removeFromLocationGroup([$entry->userID], $from->groupID);

			return $this->approveRequest('You lost your registration, due to changing your location to none of the states or territories of the United States.');
		}

		// if this is a parentID, just update the location
		if ($entry->parentID < 1) {
			$entry->update([
				'locationID' => $to->locationID,
			]);

			UserGroupUtil::removeFromLocationGroup([$entry->userID], $from->groupID);
			UserGroupUtil::addToLocationGroup([$entry->userID], $to->groupID);

			return PostUtil::updateProtocol();
		}

		// check if we already have a child in that location
		$childEntry = Entry::by([
			'parentID' => $entry->parentID,
			'locationID' => $to->locationID,
		]);

		if ($childEntry->entryID < 1) {
			$entry->update([
				'locationID' => $to->locationID,
				'registeredOn' => TIME_NOW,
				'postID' => null,
			]);

			UserGroupUtil::removeFromLocationGroup([$entry->userID], $from->groupID);
			UserGroupUtil::addToLocationGroup([$entry->userID], $to->groupID);

			return PostUtil::updateProtocol();
		}

		$entry->delete();

		UserGroupUtil::removeFromParentsGroup([$entry->userID]);
		UserGroupUtil::removeFromChildrenGroup([$entry->userID]);
		UserGroupUtil::removeFromLocationGroup([$entry->userID], $from->groupID);

		return $this->approveRequest('You lost your registration, as there was already a State-ID in your new location connected to you.');
	}

	public function validateRequestPromote() {
		$this->validateRequestPost();
	}

	public function requestPromote() {
		$currentEntry = $this->getEntryEditor();

		if ($currentEntry->entryID < 1) {
			return $this->denyRequest('You are not registered.');
		}

		if ($currentEntry->parentID > 0) {
			return $this->denyRequest('You are not registered as a Federal-ID.');
		}

		$targetName = $this->getTargetName();
		$targetUser = User::getUserByUsername($targetName);

		if ($targetUser->userID < 1) {
			return $this->denyRequest('No birth certificate found for [b]%s[/b].', [
				$targetName,
			]);
		}

		if ($targetUser->userID === $currentEntry->userID) {
			return $this->denyRequest("You are fined $50 for wasting the Government's time.");
		}

		$targetLocation = Location::byUser($targetUser);

		if ($targetLocation->locationID < 1) {
			return $this->denyRequest('[b]%s[/b] has to be registered as living in a State or Territory of the United States.', [
				$targetName,
			]);
		}

		$targetEntry = Entry::by(['userID' => $targetUser->userID]);

		// if the new parent user is already registered
		if ($targetEntry->entryID > 0) {
			// but not as your child user
			if ($targetEntry->parentID !== $currentEntry->entryID) {
				return $this->denyRequest('@%s is connected to a different Federal-ID than yourself.', [
					$targetName,
				]);
			}
			// delete the entry
			(new EntryEditor($targetEntry))->delete();
		}

		UserGroupUtil::removeFromParentsGroup([$this->user->userID]);
		UserGroupUtil::removeFromLocationGroup([$this->user->userID], $targetLocation->groupID);

		// update the parent entry to reflect promotion
		$currentEntry->update([
			'userID' => $targetUser->userID,
			'locationID' => $targetLocation->locationID,
			'registeredOn' => TIME_NOW,
		]);

		UserGroupUtil::removeFromChildrenGroup([$targetUser->userID]);
		UserGroupUtil::removeFromLocationGroup([$targetUser->userID], $targetLocation->groupID);

		UserGroupUtil::addToParentsGroup([$targetUser->userID]);
		UserGroupUtil::addToLocationGroup([$targetUser->userID], $targetLocation->groupID);

		$children = EntryList::getChildren($currentEntry->entryID);

		if (PINEHEARST_REGISTRY_MAX_CHILD_IDS > 0 && count($children) >= PINEHEARST_REGISTRY_MAX_CHILD_IDS) {
			return $this->approveRequest('@%s is now your Federal-ID. You already had the maximum number of State-IDs, so you were deregistered.', [
				$targetName,
			]);
		}

		foreach ($children as $childEntry) {
			if ($childEntry->locationID === $currentEntry->locationID) {
				return $this->approveRequest('@%s is now your Federal-ID. You already had a State-ID in %s, so you were deregistered.', [
					$targetName,
					$childEntry->locationName,
				]);
			}
		}

		// Let's create a child entry
		EntryEditor::create([
			'userID' => $currentEntry->userID,
			'locationID' => $currentEntry->locationID,
			'parentID' => $currentEntry->entryID,
			'registeredOn' => TIME_NOW,
			'postID' => null,
		]);

		$currentLocation = new Location($currentEntry->locationID);

		UserGroupUtil::addToChildrenGroup([$this->user->userID]);
		UserGroupUtil::addToLocationGroup([$this->user->userID], $currentLocation->groupID);

		return $this->approveRequest('@%s is now your Federal-ID. You remain as a State-ID in %s.', [
			$targetName,
			Location::byUser($this->user)->locationName,
		]);
	}

	public function validateRequestAdd() {
		$this->validateRequestPost();
	}

	public function requestAdd() {
		$currentEntry = $this->getEntryEditor();

		if ($currentEntry->entryID > 0) {
			return $this->denyRequest('You are already registered.');
		}

		$currentLocation = Location::byUser($this->user);

		if ($currentLocation->locationID < 1) {
			return $this->denyRequest('You have to be registered as living in a State or Territory of the United States.');
		}

		$targetName = $this->getTargetName();
		$targetUser = User::getUserByUsername($targetName);

		if ($targetUser->userID < 1) {
			return $this->denyRequest('No birth certificate found for [b]%s[/b].', [
				$targetName,
			]);
		}

		$targetEntry = Entry::by(['userID' => $targetUser->userID]);

		// if target doesn't exist or is not a parent id
		if ($targetEntry->entryID < 1 || $targetEntry->parentID > 0) {
			return $this->denyRequest('You can only connect yourself to an existing Federal-ID.');
		}

		$children = EntryList::getChildren($targetEntry->entryID);

		if (PINEHEARST_REGISTRY_MAX_CHILD_IDS > 0 && count($children) >= PINEHEARST_REGISTRY_MAX_CHILD_IDS) {
			return $this->denyRequest('@%s is already connected to the maximum number of State-IDs (%d).', [
				$targetName,
				count($children),
			]);
		}

		foreach ($children as $childEntry) {
			if ($childEntry->locationID === $currentLocation->locationID) {
				return $this->denyRequest('@%s already has a State-ID in [b]%s[/b].', [
					$targetName,
					$currentLocation->locationName,
				]);
			}
		}

		EntryEditor::create([
			'userID' => $this->user->userID,
			'parentID' => $targetEntry->entryID,
			'locationID' => $currentLocation->locationID,
			'registeredOn' => TIME_NOW,
		]);

		UserGroupUtil::addToChildrenGroup([$this->user->userID]);
		UserGroupUtil::addToLocationGroup([$this->user->userID], $currentLocation->locationID);

		return $this->approveRequest('You successfully registered as a State-ID in [b]%s[/b], connected to @%s.', [
			$currentLocation->locationName,
			$targetName,
		]);
	}

	public function validateRequestRemove() {
		$this->validateRequestPost();
	}

	public function requestRemove() {
		$currentEntry = $this->getEntryEditor();

		if ($currentEntry->entryID < 1) {
			return $this->denyRequest('You are not registered.');
		}

		$targetName = $this->getTargetName();
		$targetUser = User::getUserByUsername($targetName);

		if ($targetUser->userID < 1) {
			return $this->denyRequest('No birth certificate found for [b]%s[/b].', [
				$targetName,
			]);
		}

		if ($targetUser->userID === $this->user->userID) {
			return $this->denyRequest('If you want to deregister yourself, please use [quote]Deregister[/quote]');
		}

		$targetEntry = Entry::by(['userID' => $targetUser->userID]);

		if ($targetEntry->entryID < 1 || $targetEntry->parentID !== $currentEntry->entryID) {
			return $this->denyRequest('@%s is not registered.', [
				$targetName,
			]);
		}

		$targetLocation = new Location($targetEntry->locationID);

		(new EntryEditor($targetEntry))->delete();

		UserGroupUtil::removeFromChildrenGroup([$targetUser->userID]);
		UserGroupUtil::removeFromLocationGroup([$targetUser->userID], $targetLocation->groupID);

		return $this->approveRequest('You successfully removed your connection to [b]%s[/b].', [
			$targetName,
		]);
	}

	public function validateLoseRegistration() {
		// nothing to do
	}

	public function loseRegistration() {
		foreach ($this->getObjects() as $entry) {
			$children = null;

			if ($entry->parentID < 1) {
				$children = EntryList::getChildren($entry->entryID);

				foreach ($children->getObjects() as $child) {
					UserGroupUtil::removeFromChildrenGroup([$child->userID]);
					UserGroupUtil::removeFromLocationGroup([$child->userID], $child->locationGroupID);
				}
			}

			$location = new Location($entry->locationID);
			$entry->delete();

			UserGroupUtil::removeFromParentsGroup([$entry->userID]);
			UserGroupUtil::removeFromLocationGroup([$entry->userID], $location->groupID);

			$this->thresholdPost(new User($entry->userID), $children);
		}

		PostUtil::updateProtocol();
	}

	public function validateRequestUpdate() {
		$this->validateRequestPost();
	}

	public function requestUpdate() {
		PostUtil::deletePost($this->post);
		return PostUtil::updateProtocol();
	}

	public function validateRequestCheck() {
		$this->validateRequestPost();
	}

	public function requestCheck() {
		PostUtil::deletePost($this->post);
		return (new ValidityCheckCronjob)->execute(new Cronjob(null, []));
	}
}
