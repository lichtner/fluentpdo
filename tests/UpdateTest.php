<?php

use PHPUnit\Framework\TestCase;
use Envms\FluentPDO\Query;

class UpdateTest extends TestCase
{
    protected $fluent;

    public function setUp()
    {
        $pdo = new PDO("mysql:dbname=fluentdb;host=localhost", "vagrant","vagrant");

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        $pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->fluent = new Query($pdo);
    }

    public function testUpdate()
    {
        $query = $this->fluent->update('country')->set('name', 'aikavolS')->where('id', 1);
        $query->execute();

        $query2 = $this->fluent->from('country')->where('id', 1);

        $this->fluent->update('country')->set('name', 'Slovakia')->where('id', 1)->execute();

        $query3 = $this->fluent->from('country')->where('id', 1);


        self::assertEquals('UPDATE country SET name = ? WHERE id = ?', $query->getQuery(false));
        self::assertEquals(['0' => 'aikavolS','1' => '1'], $query->getParameters());
        self::assertEquals(['id' => '1', 'name' => 'aikavolS'], $query2->fetch());
        self::assertEquals(['id' => '1', 'name' => 'Slovakia'], $query3->fetch());
    }

    public function testUpdateLiteral()
    {
        $query = $this->fluent->update('article')->set('published_at', new Envms\FluentPDO\Literal('NOW()'))->where('user_id', 1);

        self::assertEquals('UPDATE article SET published_at = NOW() WHERE user_id = ?',  $query->getQuery(false));
        self::assertEquals(['0' => '1'], $query->getParameters());
    }

    public function testUpdateFromArray()
    {
        $query = $this->fluent->update('user')->set(array('name' => 'keraM', '`type`' => 'author'))->where('id', 1);
        $query->execute();

        self::assertEquals('UPDATE user SET name = ?, `type` = ? WHERE id = ?', $query->getQuery(false));
        self::assertEquals(['0' => 'keraM', '1' => 'author', '2' => '1'], $query->getParameters());
    }

    public function testUpdateLeftJoin()
    {
        $query = $this->fluent->update('user')
            ->outerJoin('country ON country.id = user.country_id')
            ->set(array('name' => 'keraM', '`type`' => 'author'))
            ->where('id', 1);

        self::assertEquals('UPDATE user OUTER JOIN country ON country.id = user.country_id SET name = ?, `type` = ? WHERE id = ?', $query->getQuery(false));
        self::assertEquals(['0' => 'keraM' , '1' => 'author', '2' => '1'], $query->getParameters());
    }

    public function testUpdateSmartJoin()
    {
        $query = $this->fluent->update('user')
            ->set(array('type' => 'author'))
            ->where('country.id', 1);

        self::assertEquals('UPDATE user LEFT JOIN country ON country.id = user.country_id SET type = ? WHERE country.id = ?', $query->getQuery(false));
        self::assertEquals(['0' => 'author', '1' => '1'], $query->getParameters());
    }

    public function testUpdateOrderLimit()
    {
        $query = $this->fluent->update('user')
            ->set(array('type' => 'author'))
            ->where('id', 2)
            ->orderBy('name')
            ->limit(1);

        self::assertEquals('UPDATE user SET type = ? WHERE id = ? ORDER BY name LIMIT 1',  $query->getQuery(false));
        self::assertEquals(['0' => 'author' ,'1' => '2'], $query->getParameters());
    }

    public function testUpdateShortCut()
    {
        $query = $this->fluent->update('user', array('type' => 'admin'), 1);

        self::assertEquals('UPDATE user SET type = ? WHERE id = ?', $query->getQuery(false));
        self::assertEquals(['0' => 'admin', '1' => '1'], $query->getParameters());
    }

    public function testUpdateZero()
    {
        $this->fluent->update('article')->set('content', '')->where('id', 1)->execute();
        $user = $this->fluent->from('article')->where('id', 1)->fetch();

        $printQuery = 'ID: ' . $user['id'] . ' - content: ' . $user['content'] ;

        $this->fluent->update('article')->set('content', 'content 1')->where('id', 1)->execute();

        $user2 = $this->fluent->from('article')->where('id', 1)->fetch();

        $printQuery2 = 'ID: ' . $user2['id'] . ' - content: ' . $user2['content'];

        self::assertEquals('ID: 1 - content: ', $printQuery);
        self::assertEquals('ID: 1 - content: content 1', $printQuery2);
    }

    public function testUpdateWhere()
    {
        $query = $this->fluent->update('users')
            ->set("`users`.`active`", 1)
            ->where("`country`.`name`", 'Slovakia')
            ->where("`users`.`name`", 'Marek');

        $query2 = $this->fluent->update('users')
            ->set("[users].[active]", 1)
            ->where("[country].[name]", 'Slovakia')
            ->where("[users].[name]", 'Marek');

        self::assertEquals('UPDATE users LEFT JOIN country ON country.id = users.country_id SET `users`.`active` = ? WHERE `country`.`name` = ? AND `users`.`name` = ?', $query->getQuery(false));
        self::assertEquals(['0' => '1','1' => 'Slovakia','2' => 'Marek'], $query->getParameters());
        self::assertEquals('UPDATE users LEFT JOIN country ON country.id = users.country_id SET [users].[active] = ? WHERE [country].[name] = ? AND [users].[name] = ?', $query2->getQuery(false));
        self::assertEquals(['0' => '1', '1' => 'Slovakia', '2' => 'Marek'], $query2->getParameters());
    }



}