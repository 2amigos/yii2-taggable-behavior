# Taggable Behavior for Yii 2

[![Latest Version](https://img.shields.io/github/tag/2amigos/yii2-taggable-behavior.svg?style=flat-square&label=release)](https://github.com/2amigos/yii2-taggable-behavior/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/2amigos/yii2-taggable-behavior/master.svg?style=flat-square)](https://travis-ci.org/2amigos/yii2-taggable-behavior)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/2amigos/yii2-taggable-behavior.svg?style=flat-square)](https://scrutinizer-ci.com/g/2amigos/yii2-taggable-behavior/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/2amigos/yii2-taggable-behavior.svg?style=flat-square)](https://scrutinizer-ci.com/g/2amigos/yii2-taggable-behavior)
[![Total Downloads](https://img.shields.io/packagist/dt/2amigos/yii2-taggable-behavior.svg?style=flat-square)](https://packagist.org/packages/2amigos/yii2-taggable-behavior)

This extension provides behavior functions for tagging.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require 2amigos/yii2-taggable-behavior:~1.0
```

or add

```
"2amigos/yii2-taggable-behavior": "~1.0"
```

to the `require` section of your `composer.json` file.

## Configuring

First you need to configure model as follows:

```php
use dosamigos\taggable\Taggable;

class Tour extends ActiveRecord
{
    public function behaviors() {
        return [
            [
                'class' => Taggable::className(),
            ],
        ];
    }
}
```

## Usage

First you need to create a `tbl_tag` (you can choose the name you wish) table with the following format, and build the
correspondent `ActiveRecord` class (i.e. `Tag`):

```
+-----------+
|  tbl_tag  |
+-----------+
| id        |
| frequency |
| name      |
+-----------+
```

After, if you wish to link tags to a certain `ActiveRecord` (lets say `Tour`), you need to create the table that will
link the `Tour` Model to the `Tag`:

```
+-------------------+
| tbl_tour_tag_assn |
+-------------------+
| tour_id           |
| tag_id            |
+-------------------+
```

Next, we need to configure the relationship with `Tour`:

```php
/**
 * @return \yii\db\ActiveQuery
 */
public function getTags()
{
    return $this->hasMany(Tag::className(), ['id' => 'tag_id'])->viaTable('tbl_tour_tag_assn', ['tour_id' => 'id']);
}
```

Its important to note that if you use a different name, the behavior's `$relation` attribute should be changed
accordingly.

Finally, setup the behavior, and the attribute + rule that is going to work with it in our `Tour` class,
on this case we are going to use defaults `tagNames`:

```php
/**
 * @inheritdoc
 */
public function rules()
{
    return [
        // ...
        [['tagNames'], 'safe'],
        // ...
    ];
}

/**
 * @inheritdoc
 */
public function behaviors()
{
    return [
        // for different configurations, please see the code
        // we have created tables and relationship in order to
        // use defaults settings
        Taggable::className(),
    ];
}
```

Thats it, we are now ready to use tags with our model. For example, this is how to use it in our forms together with our
[Selectize Widget](https://github.com/2amigos/yii2-selectize-widget):


```php

// On TagController (example)
// actionList to return matched tags
public function actionList($query)
{
    $models = Tag::findAllByName($query);
    $items = [];

    foreach ($models as $model) {
        $items[] = ['name' => $model->name];
    }
    // We know we can use ContentNegotiator filter
    // this way is easier to show you here :)
    Yii::$app->response->format = Response::FORMAT_JSON;

    return $items;
}


// On our form
<?= $form->field($model, 'tagNames')->widget(SelectizeTextInput::className(), [
    // calls an action that returns a JSON object with matched
    // tags
    'loadUrl' => ['tag/list'],
    'options' => ['class' => 'form-control'],
    'clientOptions' => [
        'plugins' => ['remove_button'],
        'valueField' => 'name',
        'labelField' => 'name',
        'searchField' => ['name'],
        'create' => true,
    ],
])->hint('Use commas to separate tags') ?>
```

As you can see, `tagNames` is the attribute (by default) from which we can access our tags and they are stored in it as
names separated by commas if you defined your attribute `tagNames` as string or null, if you define `tagNames` as an
array, it will be filled with the related tags.

Once you post a form with the above field, the tags will be automatically saved and linked to our `Tour` model.

## Testing

```bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Antonio Ramirez](https://github.com/tonydspaniard)
- [Alexander Kochetov](https://github.com/creocoder)
- [All Contributors](https://github.com/2amigos/yii2-selectize-widget/graphs/contributors)

## License

The BSD License (BSD). Please see [License File](LICENSE.md) for more information.

<blockquote>
    <a href="http://www.2amigos.us"><img src="http://www.gravatar.com/avatar/55363394d72945ff7ed312556ec041e0.png"></a><br>
    <i>web development has never been so fun</i><br>
    <a href="http://www.2amigos.us">www.2amigos.us</a>
</blockquote>
