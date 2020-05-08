<?php
namespace wcf\util\pinehearst\registry;

use DateTime;
use wbb\data\board\Board;
use wbb\data\board\BoardCache;
use wbb\data\post\Post;
use wbb\data\post\PostAction;
use wbb\data\post\PostEditor;
use wbb\data\thread\Thread;
use wcf\data\pinehearst\registry\EntryList;
use wcf\data\pinehearst\registry\Location;
use wcf\data\pinehearst\registry\LocationList;
use wcf\data\user\User;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\WCF;
use wcf\util\DateUtil;
use wcf\util\pinehearst\RegistryUtil;

final class PostUtil {
	private const TEMPLATE_APPLICATION = 'wcf';

	public static function getRegistryUser() {
		return new User(PINEHEARST_REGISTRY_USER_ID);
	}

	public static function getValidBoardIDs(int $parentBoardID = null): array {
		$boardIDs = [];

		foreach (BoardCache::getInstance()->getAllChildIDs($parentBoardID) as $boardID) {
			$board = BoardCache::getInstance()->getBoard($boardID);
			if ($board->countUserPosts > 0) {
				$boardIDs[] = $board->boardID;
			}
		}

		return $boardIDs;
	}

	public static function deletePost(Post $post): array {
		$action = new PostAction([$post], 'delete', [
			'noTrashing' => true,
		]);
		return $action->executeAction();
	}

	public static function createRelocationPost(User $user, Location $from, Location $to) {
		return self::createPostFromTemplate(
			PINEHEARST_REGISTRY_RELOCATIONS_THREAD_ID,
			'Relocation',
			[
				'from' => $from->locationID ? $from->locationName : 'an unknown location',
				'to' => $to->locationID ? $to->locationName : 'an unknown location',
				'username' => $user->username,
				'userID' => $user->userID,
			]
		);
	}

	public static function createResponsePost(array $args = []) {
		return self::createPostFromTemplate(PINEHEARST_REGISTRY_RESPONSES_THREAD_ID, 'Response', $args);
	}

	private static function createPostFromTemplate(int $threadID, string $templateName, array $args = []) {
		return self::createPost(
			$threadID,
			WCF::getTPL()->fetch(
				sprintf('pinehearstRegistry%s', $templateName),
				self::TEMPLATE_APPLICATION,
				$args
			)
		);
	}

	private static function createPost(int $threadID, string $message) {
		$user = self::getRegistryUser();

		$htmlInputProcessor = new HtmlInputProcessor();
		$htmlInputProcessor->process($message, 'com.woltlab.wbb.post');

		$action = new PostAction([], 'create', [
			'data' => [
				'threadID' => $threadID,
				'userID' => $user->userID,
				'username' => $user->username,
				'message' => $message,
				'time' => TIME_NOW,
			],
			'htmlInputProcessor' => $htmlInputProcessor,
		]);
		return $action->executeAction()['returnValues'];
	}

	public static function updateProtocol() {
		$locationMap = [];

		foreach (LocationList::get() as $location) {
			$location->board = new Board($location->boardID);
			$locationMap[$location->locationID] = $location;
		}

		$entryMap = [];

		foreach (EntryList::get() as $entry) {
			$entry->user = (new User($entry->userID));

			if ($entry->postID > 0) {
				$entry->post = (new Post($entry->postID));
				$entry->thread = (new Thread($entry->threadID));
			}

			$entry->location = $locationMap[$entry->locationID];

			$date = new DateTime('@' . $entry->lastActivity);
			$diff = (new DateTime('@' . TIME_NOW))->diff($date, true);
			$entry->lastActivityAbsolute = DateUtil::format($date);
			$entry->lastActivityRelative = DateUtil::formatInterval($diff);

			$date = new DateTime('@' . $entry->registeredOn);
			$diff = (new DateTime('@' . TIME_NOW))->diff($date, true);
			$entry->registeredOnAbsolute = DateUtil::format($date);
			$entry->registeredOnRelative = DateUtil::formatInterval($diff);

			$entryMap[$entry->entryID] = $entry;
		}

		$entries = array_map(
			function ($entry) use ($entryMap) {
				if ($entry->parentID > 0) {
					$entry->parent = $entryMap[$entry->parentID];
				}
				return $entry;
			},
			array_values($entryMap)
		);

		$parents = array_filter(
			$entries,
			function ($entry) {
				return $entry->parentID < 1;
			}
		);
		$children = array_filter(
			$entries,
			function ($entry) {
				return $entry->parentID > 0;
			}
		);

		$locations = [];

		foreach ($locationMap as $locationID => $location) {
			$locations[$location->locationName] = [];

			foreach ($entries as $entry) {
				if ($entry->locationID === $locationID) {
					$locations[$location->locationName][] = $entry;
				}
			}
		}

		$args = [
			'locations' => $locations,
			'children' => $children,
			'parents' => $parents,
			'now' => DateUtil::format(),
		];

		$message = WCF::getTPL()->fetch('pinehearstRegistryProtocol', self::TEMPLATE_APPLICATION, $args);

		$htmlInputProcessor = new HtmlInputProcessor();
		$htmlInputProcessor->process($message, 'com.woltlab.wbb.post');

		$user = self::getRegistryUser();
		$post = new Post(PINEHEARST_REGISTRY_PROTOCOL_POST_ID);
		$postEditor = new PostEditor($post);
		$postEditor->update([
			'userID' => $user->userID,
			'username' => $user->username,
			'editorID' => $user->userID,
			'editor' => $user->username,
			'message' => $htmlInputProcessor->getHtml(),
			'editReason' => 'Automatic Update',
			'editCount' => $post->editCount + 1,
			'lastEditTime' => TIME_NOW,
		]);
	}
}
