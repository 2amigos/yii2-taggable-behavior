<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\models;

use dosamigos\taggable\Taggable;

/**
 * Post
 *
 * @property integer $id
 * @property string $title
 * @property string $body
 *
 * @property string $tagValues
 *
 * @property Tag[] $tags
 */
class Post extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'post';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'taggable' => Taggable::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'body'], 'required'],
            ['tagNames', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])->viaTable('post_tag_assn', ['post_id' => 'id']);
    }
}
