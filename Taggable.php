<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2013 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace dosamigos\taggable;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class Taggable extends Behavior
{
	/**
	 * @var ActiveRecord the owner of this behavior.
	 */
	public $owner;
	/**
	 * @var string
	 */
	public $attribute = 'tagNames';
	/**
	 * @var string
	 */
	public $name = 'name';
	/**
	 * @var string
	 */
	public $frequency = 'frequency';
	/**
	 * @var string
	 */
	public $relation = 'tags';

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
			ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
		];
	}

	/**
	 * @param Event $event
	 */
	public function afterFind($event)
	{
        $items = [];

        foreach ($this->owner->{$this->relation} as $tag) {
            $items[] = $tag->{$this->name};
        }

        $this->owner->{$this->attribute} = is_array($this->owner->{$this->attribute})
            ? $items
            : implode(', ', $items);
	}

	/**
	 * @param Event $event
	 */
	public function afterSave($event)
	{
		if ($this->owner->{$this->attribute} === null) {
			return;
		}

		if (!$this->owner->getIsNewRecord()) {
			$this->beforeDelete($event);
		}

		$names = array_unique(preg_split(
			'/\s*,\s*/u',
			preg_replace(
				'/\s+/u',
				' ',
				is_array($this->owner->{$this->attribute})
					? implode(',', $this->owner->{$this->attribute})
					: $this->owner->{$this->attribute}
			),
			-1,
			PREG_SPLIT_NO_EMPTY
		));

		$relation = $this->owner->getRelation($this->relation);
		$pivot = $relation->via->from[0];
		/** @var ActiveRecord $class */
		$class = $relation->modelClass;
		$rows = [];

		foreach ($names as $name) {
			$tag = $class::findOne([$this->name => $name]);

			if ($tag === null) {
				$tag = new $class();
				$tag->{$this->name} = $name;
			}

			$tag->{$this->frequency}++;

			if (!$tag->save()) {
				continue;
			}

			$rows[] = [$this->owner->getPrimaryKey(), $tag->getPrimaryKey()];
		}

		if (!empty($rows)) {
			$this->owner->getDb()
				->createCommand()
				->batchInsert($pivot, [key($relation->via->link), current($relation->link)], $rows)
				->execute();
		}
	}

	/**
	 * @param Event $event
	 */
	public function beforeDelete($event)
	{
		$relation = $this->owner->getRelation($this->relation);
		$pivot = $relation->via->from[0];
		/** @var ActiveRecord $class */
		$class = $relation->modelClass;
		$query = new Query();
		$pks = $query
			->select(current($relation->link))
			->from($pivot)
			->where([key($relation->via->link) => $this->owner->getPrimaryKey()])
			->column($this->owner->getDb());

		if (!empty($pks)) {
			$class::updateAllCounters([$this->frequency => -1], ['in', $class::primaryKey(), $pks]);
		}

		$this->owner->getDb()
			->createCommand()
			->delete($pivot, [key($relation->via->link) => $this->owner->getPrimaryKey()])
			->execute();
	}
}
