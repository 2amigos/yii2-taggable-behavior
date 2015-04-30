<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\models;
use yii\behaviors\SluggableBehavior;

/**
 * Tag
 *
 * @property integer $id
 * @property string $name
 * @property integer $frequency
 */
class Tag extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tag';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'sluggable' => [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'required'],
        ];
    }
}
