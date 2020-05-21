<?php
namespace wcf\util\pinehearst\registry;

use wbb\data\board\BoardCache;
use wbb\system\board\BoardPermissionCache;
use wcf\data\user\UserProfile;

final class BoardUtil {
	public static function getValidBoardIDs(int $parentBoardID = null): array {
		$boardIDs = [];

		if ($parentBoardID > 0) {
			$boardIDs[] = $parentBoardID;
		}

		foreach (BoardCache::getInstance()->getAllChildIDs($parentBoardID) as $boardID) {
			$board = BoardCache::getInstance()->getBoard($boardID);
			if ($board->countUserPosts > 0) {
				$boardIDs[] = $board->boardID;
			}
		}

		return $boardIDs;
	}

	public static function getGuestBoardIDs(): array {
		$guest = UserProfile::getGuestUserProfile('guest');
		$permissions = BoardPermissionCache::getInstance()->getPermissions(
			$guest->getDecoratedObject()
		);
		$permission = 'canViewBoard';
		$boardIDs = [];

		foreach (BoardCache::getInstance()->getBoards() as $board) {
			if ($board->isPrivate) {
				continue;
			}

			if (isset($permissions[$board->boardID][$permission])) {
				if (!$permissions[$board->boardID][$permission]) {
					continue;
				}
			}
			elseif (!$guest->getPermission('user.board.'.$permission)) {
				continue;
			}

			if ($guest->getNeverPermission('user.board.'.$permission)) {
				continue;
			}

			$boardIDs[] = $board->boardID;
		}

		return $boardIDs;
	}
}
