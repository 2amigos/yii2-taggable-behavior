Taggable behavior for Yii 2
===========================

This extension allows you to get tagging functionality.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```sh
php composer.phar require dosamigos/yii2-taggable-behavior "*"
```

or add

```json
"dosamigos/yii2-taggable-behavior": "*"
```

to the require section of your `composer.json` file.

Configuring
--------------------------

First you need to configure model as follows:

```php
class Post extends ActiveRecord
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

Second you need to configure query model as follows:

```php
class PostQuery extends ActiveQuery
{
	public function behaviors() {
		return [
			[
				'class' => TaggableQuery::className(),
			],
		];
	}
}
```
