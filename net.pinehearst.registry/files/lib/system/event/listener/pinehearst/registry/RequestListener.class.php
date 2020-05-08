<?php
namespace wcf\system\event\listener\pinehearst\registry;

use wbb\data\post\Post;
use wcf\data\pinehearst\registry\Entry;
use wcf\data\pinehearst\registry\EntryAction;
use wcf\data\pinehearst\registry\EntryEditor;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\util\pinehearst\RegistryUtil;
use wcf\util\StringUtil;

class RequestListener implements IParameterizedEventListener {
	private const
		REQUEST_TYPES = [
			'/^Register$/i' => EntryAction::REQUEST_REGISTER,
			'/^Register:(.+)$/i' => EntryAction::REQUEST_ADD,
			'/^Promote:(.+)$/i' => EntryAction::REQUEST_PROMOTE,
			'/^Deregister$/i' => EntryAction::REQUEST_DEREGISTER,
			'/^Deregister:(.+)$/i' => EntryAction::REQUEST_REMOVE,
			'/^Update$/i' => EntryAction::REQUEST_UPDATE,
			'/^Check$/i' => EntryAction::REQUEST_CHECK,
		];

	/**
	 * @inheritDoc
	 */
	// listens to wcf\system\message\QuickReplyManager->createdMessage
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$post = $parameters['message'];

		if (get_class($post) !== Post::class) {
			return;
		}

		if ($post->userID < 1) {
			return;
		}

		$entry = Entry::by(['userID' => $post->userID]);

		(new EntryEditor($entry))->updateLatestPost($post);

		$thread = $post->getThread();

		if ($thread->threadID !== PINEHEARST_REGISTRY_REQUESTS_THREAD_ID) {
			return;
		}

		$request = $post->getPlainTextMessage();
		$do = EntryAction::REQUEST_UNKNOWN;
		$target = null;

		foreach (self::REQUEST_TYPES as $pattern => $action) {
			if (preg_match($pattern, $request, $matches)) {
				$do = $action;
				$target = StringUtil::trim($matches[1] ?? '');
				if (substr($target, 0, 1) === '@') {
					$target = substr($target, 1);
				}
				break;
			}
		}

		$action = new EntryAction([$entry], $do, [
			'post' => $post,
			'target' => $target,
		]);
		$action->validateAction();
		$action->executeAction();
	}
}
