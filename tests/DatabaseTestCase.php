<?php
/**
 * @link https://github.com/2amigos/yii2-taggable-behavior
 * @copyright Copyright (c) 2013-2015 2amigOS! Consulting Group LLC
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests;

use Yii;

/**
 * DatabaseTestCase
 */
abstract class DatabaseTestCase extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        if (Yii::$app->get('db', false) === null) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
    }
}
