<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2014 2amigOS! Consulting Group LLC
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace dosamigos\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;

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
    public $relation = 'tags';
    /**
     * Tag values
     * @var array|string
     */
    public $tagValues;
    /**
     * @var bool
     */
    public $asArray = false;
    /**
     * @var \Closure
     */
    public $beforeUnlink;
    /**
     * @var \Closure
     */
    public $afterUnlink;
    /**
     * @var \Closure
     */
    public $beforeLink;
    /**
     * @var \Closure
     */
    public $afterLink;
    /**
     * @var array
     */
    private $_old_tags;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute ? : parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute ? : parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name == $this->attribute) {
            return $this->tagValues ? : $this->getTagNames();
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name == $this->attribute) {
            $this->tagValues = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    private function getTagNames()
    {
        $items = $this->owner->isNewRecord ? [] : array_keys($this->getOldTags());
        return $this->asArray ? $items : implode(',', $items);
    }

    /**
     * @return ActiveRecord[]
     */
    private function getOldTags()
    {
        if ($this->_old_tags === null) {
            $this->_old_tags = $this->owner
                ->getRelation($this->relation)
                ->indexBy($this->name)
                ->all();
        }
        return $this->_old_tags;
    }

    public function afterSave()
    {
        if ($this->tagValues === null) {
            if ($this->owner->{$this->attribute} !== null) {
                $this->tagValues = $this->owner->{$this->attribute};
            } else {
                return;
            }
        }

        $value = is_array($this->tagValues) ? $this->tagValues : explode(',', $this->tagValues);
        $value = array_map('trim', $value);
        $names = array_unique($value);

        $old = $this->getOldTags();
        $new = array_flip($names);

        $update = array_intersect_key($old, $new);
        $delete = array_diff_key($old, $update);
        $create = array_diff_key($new, $update);

        /** @var ActiveRecord $class */
        $class = $this->owner->getRelation($this->relation)->modelClass;

        foreach ($create as $name => $key) {
            $condition = [$this->name => $name];
            $tag = $class::findOne($condition) ?: (new $class($condition));
            $this->link($tag);
        }

        foreach ($delete as $tag) {
            $this->unlink($tag);
        }

        if ($this->afterLink instanceof \Closure) {
            foreach($update as $tag) {
                call_user_func($this->afterLink, $tag);
            }
        }
    }

    public function beforeDelete()
    {
        $this->owner->unlinkAll($this->relation, true);
    }

    /**
     * @param $tag ActiveRecord
     */
    protected function link($tag)
    {
        if ($this->beforeLink instanceof \Closure) {
            call_user_func($this->beforeLink, $tag);
        }

        $tag->save() && $this->owner->link($this->relation, $tag);

        if ($this->afterLink instanceof \Closure) {
            call_user_func($this->afterLink, $tag);
        }
    }

    /**
     * @param $tag ActiveRecord
     */
    protected function unlink($tag)
    {
        if ($this->beforeUnlink instanceof \Closure) {
            call_user_func($this->beforeUnlink, $tag);
        }

        $this->owner->unlink($this->relation, $tag, true);

        if ($this->afterUnlink instanceof \Closure) {
            call_user_func($this->afterUnlink, $tag);
        }
    }
}
