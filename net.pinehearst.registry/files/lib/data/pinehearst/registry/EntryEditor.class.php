<?php
namespace wcf\data\pinehearst\registry;

use wbb\data\post\Post;
use wcf\data\DatabaseObjectEditor;
use wcf\util\pinehearst\registry\PostUtil;

class EntryEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Entry::class;

	public function updateLatestPost(Post $post) {
		if ($this->entryID < 1) {
			return;
		}

		$location = new Location($this->locationID);

		$boardIDs = PostUtil::getValidBoardIDs(
			// child-ids check only posts in their respective state board
			$this->parentID > 0 ? $location->boardID : null
		);

		if (in_array($post->getThread()->boardID, $boardIDs)) {
			$this->update(['postID' => $post->postID]);
		}
	}
}
