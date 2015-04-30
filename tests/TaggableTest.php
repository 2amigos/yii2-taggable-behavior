<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests;

use tests\models\Post;
use Yii;
use yii\db\Connection;

/**
 * TaggableBehaviorTest
 */
class TaggableBehaviorTest extends DatabaseTestCase
{
    /**
     * @expectedException \yii\base\UnknownPropertyException
     */
    public function testCanGetProperty()
    {
        $post = new Post();
        $post->notExistentProperty;
    }

    /**
     * @expectedException \yii\base\UnknownPropertyException
     */
    public function testCanSetProperty()
    {
        $post = new Post();
        $post->notExistentProperty = 'test';
    }

    public function testFindPosts()
    {
        $data = [];
        $posts = Post::find()->with('tags')->all();

        foreach ($posts as $post) {
            $data[] = $post->toArray([], ['tags']);
        }

        $this->assertEquals(require(__DIR__ . '/data/test-find-posts.php'), $data);
    }

    public function testFindPost()
    {
        $post = Post::findOne(2);
        $this->assertEquals('tag 2, tag 3, tag 4', $post->tagNames);
    }

    public function testCreatePost()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testCreatePostSetTagValues()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => 'tag 4, tag  4, tag 5, , tag 6',
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testCreatePostSetTagValuesAsArray()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => ['tag 4', 'tag  4', 'tag 5', '', 'tag 6'],
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePost()
    {
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValues()
    {
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = 'tag 3, tag  3, tag 4, , tag 6';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValuesAsArray()
    {
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = ['tag 3', 'tag  3', 'tag 4', '', 'tag 6'];
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeletePost()
    {
        $post = Post::findOne(2);
        $this->assertEquals(1, $post->delete());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-delete-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'sqlite::memory:',
            ]);

            Yii::$app->getDb()->open();
            $lines = explode(';', file_get_contents(__DIR__ . '/migrations/sqlite.sql'));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    Yii::$app->getDb()->pdo->exec($line);
                }
            }
        } catch (\Exception $e) {
            Yii::$app->clear('db');
        }
    }
}
