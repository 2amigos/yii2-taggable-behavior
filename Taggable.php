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
    public $frequency = 'frequency';
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
     * @var null|array
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
        return $name == $this->attribute ?: parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name == $this->attribute) {
            return $this->getTagNames();
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute ?: parent::canSetProperty($name, $checkVars);
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
        $items = array_keys($this->getOldTags());
        return $this->asArray ? $items : implode(', ', $items);
    }

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

        $names = array_unique(preg_split(
            '/\s*,\s*/u',
            preg_replace(
                '/\s+/u',
                ' ',
                is_array($this->tagValues)
                    ? implode(',', $this->tagValues)
                    : $this->tagValues
            ),
            -1,
            PREG_SPLIT_NO_EMPTY
        ));

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
    }

    public function beforeDelete()
    {
        foreach ($this->getOldTags() as $tag) {
            $this->unlink($tag);
        }
    }

    /**
     * @param $tag ActiveRecord
     */
    protected function link($tag)
    {
        $tag->{$this->frequency}++;
        if ($tag->save()) {
            $this->owner->link($this->relation, $tag);
        }
    }

    /**
     * @param $tag ActiveRecord
     */
    protected function unlink($tag)
    {
        $tag->{$this->frequency}--;
        $tag->update();
        $this->owner->unlink($this->relation, $tag, true);
    }
}
