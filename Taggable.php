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
 *
 * @property ActiveRecord $owner
 */
class Taggable extends Behavior
{
    /**
     * @var string
     */
    public $attribute = 'tagNames';
    /**
     * @var string
     */
    public $relationAttribute = 'name';
    /**
     * @var string
     */
    public $relation = 'tags';
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
     * @var \Closure
     */
    public $getItem;
    /**
     * @var array
     */
    private $_old_tags;
    /**
     * @var array
     */
    private $_attributeValue;

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
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute ?: parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        return $name == $this->attribute ? $this->getAttributeValue() : null;
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($name == $this->attribute) {
            $this->setAttributeValue($value);
        }
    }

    public function getAttributeValue()
    {
        if ($this->_attributeValue === null) {
            $items = $this->owner->isNewRecord ? [] : array_keys($this->getOldTags());
            $this->_attributeValue = $this->asArray ? $items : implode(',', $items);
        }
        return $this->_attributeValue;
    }

    public function setAttributeValue($value)
    {
        if (is_array($value)) {
            $this->_attributeValue = $value;
        } elseif (is_string($value)) {
            $this->_attributeValue = explode(',', $value);
        }
    }

    /**
     * @return ActiveRecord[]
     */
    private function getOldTags()
    {
        if ($this->_old_tags === null) {
            $this->_old_tags = $this->owner
                ->getRelation($this->relation)
                ->indexBy($this->relationAttribute)
                ->all();
        }
        return $this->_old_tags;
    }

    public function afterSave()
    {
        if (empty($this->_attributeValue)) {
            return;
        }

        $value = $this->_attributeValue;
        $value = array_map('trim', $value);
        $value = array_unique($value);
        $value = array_filter($value);

        $old = $this->getOldTags();
        $new = array_flip($value);

        $update = array_intersect_key($old, $new);
        $delete = array_diff_key($old, $update);
        $create = array_diff_key($new, $update);

        /** @var ActiveRecord $class */
        $class = $this->owner->getRelation($this->relation)->modelClass;

        foreach ($create as $name => $key) {
            $tag = $this->getItem($name, $class);
            $this->link($tag);
        }

        foreach ($delete as $tag) {
            $this->unlink($tag);
        }

        if ($this->afterLink instanceof \Closure) {
            foreach ($update as $tag) {
                call_user_func($this->afterLink, $tag);
            }
        }
    }

    public function beforeDelete()
    {
        $this->owner->unlinkAll($this->relation, true);
    }

    protected function getItem($name, $class)
    {
        if ($this->getItem instanceof \Closure) {
            return call_user_func($this->getItem, $name, $class);
        } else {
            $condition = [$this->relationAttribute => $name];
            return $class::findOne($condition) ?: new $class($condition);
        }
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
