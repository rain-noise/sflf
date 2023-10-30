<?php
use Model\Article;
use Model\User;

/**
 * Daoテストケース
 */
class DaoTest extends SflfTestCase
{
    protected function setUp() : void
    {
        static::setUpDatabase();
        $this->setUpDataSet([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ---------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => null,
        ]);
    }

    protected function tearDown() : void
    {
        static::tearDownDatabase();
    }

    public function test_db()
    {
        $this->assertNotNull(Dao::db());
    }

    public function test_db_after_close()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Do not connect database yet, or already closed connection.");

        Dao::close();
        Dao::db();
    }

    public function test_quoteIdentifier()
    {
        $this->assertSame('`table_name`', Dao::quoteIdentifier('table_name'));
        $this->assertSame('`table_name`', Dao::quoteIdentifier('`table_name`'));
    }

    public function test_setSqlLogger()
    {
        $sql_log = [];
        Dao::setSqlLogger(function ($sql) use (&$sql_log) {
            $sql_log[] = $sql->emulate();
        });

        $this->assertEquals($sql_log, []);

        Dao::find("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 1], User::class);

        $this->assertEquals($sql_log, [
            "/* Emulated SQL */ SELECT * FROM users WHERE user_id = 1"
        ]);
    }

    public function test_connect_success()
    {
        // This method used in setUpDatabase(), so do nothing.
        $this->success();
    }

    public function test_connect_failed()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches("/Dao::connect failed\. : 1045 Access denied for user 'sflf'@'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}' \(using password: YES\)/");

        Dao::close();
        Dao::connect('mariadb', 'sflf', 'invalid_pass', 'sflf');
    }

    public function test_begin_commit_rollback()
    {
        $this->assertEquals(3, Dao::count("SELECT * FROM users"));
        Dao::begin();
        $this->assertEquals(1, Dao::queryAffect("DELETE FROM users WHERE user_id = 1"));
        $this->assertEquals(2, Dao::count("SELECT * FROM users"));
        Dao::rollback();
        $this->assertEquals(3, Dao::count("SELECT * FROM users"));

        Dao::begin();
        $this->assertEquals(1, Dao::queryAffect("DELETE FROM users WHERE user_id = 1"));
        $this->assertEquals(2, Dao::count("SELECT * FROM users"));
        Dao::commit();
        $this->assertEquals(2, Dao::count("SELECT * FROM users"));
    }

    public function test_transaction_success()
    {
        Dao::transaction(function () {
            $this->assertEquals(1, Dao::queryAffect("DELETE FROM users WHERE user_id = 1"));
        });
        $this->assertEquals(2, Dao::count("SELECT * FROM users"));
    }

    public function test_transaction_failed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Something happened.");

        try {
            Dao::transaction(function () {
                $this->assertEquals(1, Dao::queryAffect("DELETE FROM users WHERE user_id = 1"));
                throw new Exception("Something happened.");
            });
        } finally {
            $this->assertEquals(3, Dao::count("SELECT * FROM users"));
        }
    }

    public function test_escape()
    {
        $this->assertEquals('', Dao::escape(''));
        $this->assertEquals('foo', Dao::escape('foo'));
        $this->assertEquals("foo\\'s", Dao::escape("foo's"));
        $this->assertEquals('\\"foo\\"', Dao::escape('"foo"'));
        $this->assertEquals('1', Dao::escape(1));
    }

    public function test_find_not_exists()
    {
        $user = Dao::find("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 0], User::class);
        $this->assertNull($user);
    }

    public function test_getInsertId()
    {
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(1, Dao::getInsertId());

        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 2', 'Body 2')"));
        $this->assertEquals(2, Dao::getInsertId());
    }

    public function test_truncate()
    {
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(1, Dao::getInsertId());
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 2', 'Body 2')"));
        $this->assertEquals(2, Dao::getInsertId());
        $this->assertEquals(2, Dao::count("SELECT * FROM articles"));

        Dao::truncate('articles');
        $this->assertEquals(0, Dao::count("SELECT * FROM articles"));

        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(1, Dao::getInsertId());
    }

    public function test_setAutoIncrement()
    {
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(1, Dao::getInsertId());
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 2', 'Body 2')"));
        $this->assertEquals(2, Dao::getInsertId());
        $this->assertEquals(2, Dao::count("SELECT * FROM articles"));

        $this->assertEquals(2, Dao::queryAffect('DELETE FROM articles'));
        $this->assertEquals(0, Dao::count("SELECT * FROM articles"));

        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(3, Dao::getInsertId());
        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 2', 'Body 2')"));
        $this->assertEquals(4, Dao::getInsertId());
        $this->assertEquals(2, Dao::count("SELECT * FROM articles"));

        $this->assertEquals(2, Dao::queryAffect('DELETE FROM articles'));
        Dao::setAutoIncrement('articles', 1);
        $this->assertEquals(0, Dao::count("SELECT * FROM articles"));

        $this->assertEquals(1, Dao::queryAffect("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')"));
        $this->assertEquals(1, Dao::getInsertId());
    }

    public function test_query()
    {
        Dao::query("INSERT INTO articles (user_id, subject, body) VALUES (1, 'Subject 1', 'Body 1')");
        Dao::query("INSERT INTO articles (user_id, subject, body) VALUES (:user_id, :subject, :body)", [
            'user_id' => 1,
            'subject' => 'Subject 2',
            'body'    => 'Body 2',
        ]);
        $article          = new Article();
        $article->user_id = 1;
        $article->subject = 'Subject 3';
        $article->body    = 'Body 3';
        Dao::query("INSERT INTO articles (user_id, subject, body) VALUES (:user_id, :subject, :body)", $article);

        $this->assertEquals(3, Dao::count("SELECT * FROM articles"));

        $rs = Dao::query("SELECT * FROM articles WHERE article_id = 1");
        $rs = $rs->fetch_assoc();
        $this->assertEquals(1, $rs['article_id']);
        $this->assertEquals(1, $rs['user_id']);
        $this->assertEquals('Subject 1', $rs['subject']);
        $this->assertEquals('Body 1', $rs['body']);

        Dao::query("UPDATE articles SET subject = 'foo' WHERE article_id = 1");
        $rs = Dao::query("SELECT * FROM articles WHERE article_id = 1");
        $rs = $rs->fetch_assoc();
        $this->assertEquals('foo', $rs['subject']);

        $affected_rows = Dao::query("INSERT INTO users (user_id, name, gender, birthday, email, role, password) VALUES (4, 'name', 1, '1980-01-01', 'name@sflf.local', 'admin', 'dummy')");
        $this->assertEquals(1, $affected_rows);

        $affected_rows = Dao::query("UPDATE users SET name = 'foo' WHERE gender = 1");
        $this->assertEquals(3, $affected_rows);
    }

    public function test_query_error()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Execute query failed : 1054 Unknown column 'invalid_col' in 'where clause'\n--- [SQL] ---\nSELECT * FROM users WHERE invalid_col = 'invalid'\n-------------\n");

        Dao::query("SELECT * FROM users WHERE invalid_col = 'invalid'");
    }

    public function test_querySelect()
    {
        $rs = Dao::querySelect("SELECT * FROM users");
        $this->assertInstanceOf(mysqli_result::class, $rs);
        $this->assertEquals(3, $rs->num_rows);

        $rs = Dao::querySelect("SELECT * FROM users WHERE gender = :gender", ['gender' => 1]);
        $this->assertInstanceOf(mysqli_result::class, $rs);
        $this->assertEquals(2, $rs->num_rows);

        $rs = Dao::querySelect("SELECT * FROM users WHERE gender = :gender AND role = :role", ['gender' => 1, 'role' => 'admin']);
        $this->assertInstanceOf(mysqli_result::class, $rs);
        $this->assertEquals(0, $rs->num_rows);
    }

    public function test_querySelect_error()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Invalid SQL query given, querySelect() MUST a SELECT/SHOW/DESCRIBE/EXPLAIN query.");

        Dao::querySelect("INSERT INTO users (user_id, name, gender, birthday, email, role, password) VALUES (4, 'name', 1, '1980-01-01', 'name@sflf.local', 'admin', 'dummy')");
    }

    public function test_queryAffect()
    {
        $affected_row = Dao::queryAffect("INSERT INTO users (user_id, name, gender, birthday, email, role, password) VALUES (4, 'name', 1, '1980-01-01', 'name@sflf.local', 'admin', 'dummy')");
        $this->assertEquals(1, $affected_row);

        $affected_row = Dao::queryAffect("UPDATE users SET name = 'dummy' WHERE gender = :gender", ['gender' => 1]);
        $this->assertEquals(3, $affected_row);
    }

    public function test_queryAffect_error()
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage("Invalid SQL query given, queryAffect() MUST not be a SELECT/SHOW/DESCRIBE/EXPLAIN queries.");

        Dao::queryAffect("SELECT * FROM users");
    }

    public function test_each()
    {
        $ids = [];
        Dao::each(
            function ($i, $entity) use (&$ids) {
                $this->assertInstanceOf(User::class, $entity);
                $this->assertEquals(1, $entity->gender);
                $ids[$i] = $entity->user_id;
            },
            "SELECT * FROM users WHERE gender = :gender",
            ['gender' => 1],
            User::class
        );
        $this->assertEquals([2, 3], $ids);
        $this->assertExecutedQueryWildcard('/* Emulated SQL */ SELECT * FROM users WHERE gender = 1');
    }

    public function test_chunk()
    {
        $ids = [];
        Dao::chunk(
            function ($i, $entity) use (&$ids) {
                $this->assertInstanceOf(User::class, $entity);
                $this->assertEquals(1, $entity->gender);
                $ids[$i] = $entity->user_id;
            },
            function (&$params, $cursor) {
                $where = isset($params['gender']) ? ' AND gender = :gender' : '';
                if ($cursor != null) {
                    $where .= ' AND user_id > :cursor_user_id';
                    $params['cursor_user_id'] = $cursor->user_id;
                }
                return "SELECT * FROM users WHERE true {$where} ORDER BY user_id ASC";
            },
            ['gender' => 1],
            User::class,
            1
        );
        $this->assertEquals([2, 3], $ids);
        $this->assertExecutedQueryWildcard('/* Emulated SQL */ SELECT * FROM users WHERE true  AND gender = 1 ORDER BY user_id ASC LIMIT 1');
        $this->assertExecutedQueryWildcard('/* Emulated SQL */ SELECT * FROM users WHERE true  AND gender = 1 AND user_id > 2 ORDER BY user_id ASC LIMIT 1');
        $this->assertExecutedQueryWildcard('/* Emulated SQL */ SELECT * FROM users WHERE true  AND gender = 1 AND user_id > 3 ORDER BY user_id ASC LIMIT 1');
    }

    public function test_select()
    {
        $rs = Dao::select("SELECT * FROM users");
        $this->assertEquals(3, count($rs));
        $this->assertInstanceOf(stdClass::class, $rs[0]);

        $user = $rs[0];
        $this->assertEquals(1, $user->user_id);
        $this->assertEquals('Elody Bode III', $user->name);
        $this->assertEquals(2, $user->gender);
        $this->assertInstanceOf(DateTime::class, $user->birthday);
        $this->assertEquals('1990-01-08', $user->birthday->format('Y-m-d'));
        $this->assertEquals('elody@s1.sflf.local', $user->email);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci', $user->password);

        $rs = Dao::select("SELECT * FROM users WHERE gender = :gender", ['gender' => 1], User::class);
        $this->assertEquals(2, count($rs));
        $this->assertInstanceOf(User::class, $rs[0]);

        $rs = Dao::select("SELECT * FROM users WHERE gender = :gender AND role = :role", ['gender' => 1, 'role' => 'admin']);
        $this->assertEquals(0, count($rs));

        $rs = Dao::select("SELECT * FROM users WHERE user_id IN (:user_id)", ['user_id' => [2, 3]], User::class);
        $this->assertEquals(2, count($rs));
    }

    public function test_lists()
    {
        $rs = Dao::lists('name', "SELECT * FROM users");
        $this->assertEquals(3, count($rs));
        $this->assertEquals(['Elody Bode III', 'Alta Hegmann', 'Damien Kling'], $rs);

        $rs = Dao::lists('user_id', "SELECT * FROM users WHERE gender = :gender", ['gender' => 1]);
        $this->assertEquals(2, count($rs));
        $this->assertEquals([2, 3], $rs);

        $rs = Dao::lists('user_id', "SELECT * FROM users WHERE false");
        $this->assertEquals(0, count($rs));
        $this->assertEquals([], $rs);
    }

    public function test_find()
    {
        $user = Dao::find("SELECT * FROM users ORDER BY user_id ASC");
        $this->assertInstanceOf(stdClass::class, $user);
        $this->assertEquals(1, $user->user_id);
        $this->assertEquals('Elody Bode III', $user->name);
        $this->assertEquals(2, $user->gender);
        $this->assertInstanceOf(DateTime::class, $user->birthday);
        $this->assertEquals('1990-01-08', $user->birthday->format('Y-m-d'));
        $this->assertEquals('elody@s1.sflf.local', $user->email);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci', $user->password);

        $user = Dao::find("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 2], User::class);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(2, $user->user_id);

        $user = Dao::find("SELECT * FROM users WHERE gender = :gender AND role = :role", ['gender' => 1, 'role' => 'admin']);
        $this->assertNull($user);
    }

    public function test_get()
    {
        $rs = Dao::get("SELECT name FROM users ORDER BY user_id ASC");
        $this->assertEquals('Elody Bode III', $rs);

        $rs = Dao::get("SELECT user_id FROM users WHERE user_id = :user_id", ['user_id' => 2], User::class);
        $this->assertEquals(2, $rs);

        $rs = Dao::get("SELECT user_id FROM users WHERE gender = :gender AND role = :role", ['gender' => 1, 'role' => 'admin']);
        $this->assertNull($rs);
    }

    public function test_exists()
    {
        $exists = Dao::exists("SELECT * FROM users");
        $this->assertTrue($exists);

        $exists = Dao::exists("SELECT * FROM articles");
        $this->assertFalse($exists);

        $exists = Dao::exists("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 1]);
        $this->assertTrue($exists);

        $exists = Dao::exists("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 9]);
        $this->assertFalse($exists);
    }

    public function test_count()
    {
        $count = Dao::count("SELECT * FROM users");
        $this->assertSame(3, $count);

        $count = Dao::count("SELECT * FROM articles");
        $this->assertSame(0, $count);

        $count = Dao::count("SELECT * FROM users WHERE gender = :gender", ['gender' => 1]);
        $this->assertSame(2, $count);

        $count = Dao::count("SELECT * FROM users WHERE user_id = :user_id", ['user_id' => 9]);
        $this->assertSame(0, $count);
    }

    public function test_paginate()
    {
        [$pi, $rs] = Dao::paginate(-1, 2, "SELECT * FROM users");
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(1, $pi->page);
        $this->assertSame(2, $pi->page_size);
        $this->assertSame(3, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(0, $pi->offset);
        $this->assertSame(1, $pi->limit);
        $this->assertSame(2, count($rs));
        $this->assertInstanceOf(stdClass::class, $rs[0]);
        $this->assertSame(1, $rs[0]->user_id);
        $this->assertSame(2, $rs[1]->user_id);

        [$pi, $rs] = Dao::paginate(1, 2, "SELECT * FROM users");
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(1, $pi->page);
        $this->assertSame(2, $pi->page_size);
        $this->assertSame(3, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(0, $pi->offset);
        $this->assertSame(1, $pi->limit);
        $this->assertSame(2, count($rs));
        $this->assertInstanceOf(stdClass::class, $rs[0]);
        $this->assertSame(1, $rs[0]->user_id);
        $this->assertSame(2, $rs[1]->user_id);

        [$pi, $rs] = Dao::paginate(2, 2, "SELECT * FROM users");
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(2, $pi->page);
        $this->assertSame(2, $pi->page_size);
        $this->assertSame(3, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(2, $pi->offset);
        $this->assertSame(3, $pi->limit);
        $this->assertSame(1, count($rs));
        $this->assertInstanceOf(stdClass::class, $rs[0]);
        $this->assertSame(3, $rs[0]->user_id);

        [$pi, $rs] = Dao::paginate(3, 2, "SELECT * FROM users");
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(2, $pi->page);
        $this->assertSame(2, $pi->page_size);
        $this->assertSame(3, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(2, $pi->offset);
        $this->assertSame(3, $pi->limit);
        $this->assertSame(1, count($rs));
        $this->assertInstanceOf(stdClass::class, $rs[0]);
        $this->assertSame(3, $rs[0]->user_id);

        [$pi, $rs] = Dao::paginate(1, 1, "SELECT * FROM users WHERE gender = :gender", ['gender' => 1], User::class);
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(1, $pi->page);
        $this->assertSame(1, $pi->page_size);
        $this->assertSame(2, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(0, $pi->offset);
        $this->assertSame(0, $pi->limit);
        $this->assertSame(1, count($rs));
        $this->assertInstanceOf(User::class, $rs[0]);
        $this->assertSame(2, $rs[0]->user_id);

        [$pi, $rs] = Dao::paginate(1, 1, "SELECT * FROM users WHERE gender = :gender", ['gender' => 1], User::class, "SELECT count(*) FROM users WHERE gender = :gender");
        $this->assertInstanceOf(PageInfo::class, $pi);
        $this->assertSame(1, $pi->page);
        $this->assertSame(1, $pi->page_size);
        $this->assertSame(2, $pi->hit_count);
        $this->assertSame(2, $pi->max_page);
        $this->assertSame(0, $pi->offset);
        $this->assertSame(0, $pi->limit);
        $this->assertSame(1, count($rs));
        $this->assertInstanceOf(User::class, $rs[0]);
        $this->assertSame(2, $rs[0]->user_id);
    }

    public function test_insert()
    {
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ---------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $now = new DateTime('now');

        $article             = new Article();
        $article->user_id    = 1;
        $article->subject    = 'Subject 1';
        $article->body       = 'Body 1';
        $article->created_at = $now;
        $inserted_id         = Dao::insert('articles', $article);
        $this->assertSame(1, $inserted_id);

        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ---------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [
                ['article_id' , 'user_id' , 'subject'   , 'body'   , 'created_at' , 'updated_at' ],
                // ---------- | --------- | ----------- | -------- | ------------ | ------------ |
                [           1 ,         1 , 'Subject 1' , 'Body 1' , $now         , null         ],
            ],
        ]);

        $user             = new User();
        $user->user_id    = 4;
        $user->name       = 'Name';
        $user->gender     = 2;
        $user->birthday   = new DateTime('1977-01-01');
        $user->email      = 'name@s1.sflf.local';
        $user->role       = 'admin';
        $user->password   = 'Dummy';
        $user->created_at = $now;

        $inserted_id = Dao::insert('users', $user);
        $this->assertSame(0, $inserted_id);

        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                , 'role'  , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ---------------- | -------- | ------------ | ---------------------- | ------- | -------------------------------------------------------------- | --------------------- | ------------ |
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user'  , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.sflf.local' , 'user'  , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user'  , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        4 , 'Name'           ,        2 , '1977-01-01' , 'name@s1.sflf.local'   , 'admin' , 'Dummy'                                                        , $now                  , null         ],
            ],
            'articles' => [
                ['article_id' , 'user_id' , 'subject'   , 'body'   , 'created_at' , 'updated_at' ],
                // ---------- | --------- | ----------- | -------- | ------------ | ------------ |
                [           1 ,         1 , 'Subject 1' , 'Body 1' , $now         , null         ],
            ],
        ]);
    }

    public function test_update()
    {
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'           , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ---------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III' ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann'   ,        1 , '2003-02-16' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        3 , 'Damien Kling'   ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $now              = new DateTime('now');
        $user             = Dao::find("SELECT * FROM users WHERE user_id = 2", [], User::class);
        $user->name       = "{$user->name}(U)";
        $user->gender     = 2;
        $user->birthday   = $user->birthday->modify('+1 day');
        $user->updated_at = $now;
        $affected_rows    = Dao::update('users', $user, 'user_id = :user_id');

        $this->assertEquals(1, $affected_rows);
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'            , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ----------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III'  ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann(U)' ,        2 , '2003-02-17' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
                [        3 , 'Damien Kling'    ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $user->name    = "Update 1";
        $user->gender  = 1;
        $affected_rows = Dao::update('users', $user, 'user_id = :user_id', [
            'where' => [
                'user_id' => 99
            ]
        ]);

        $this->assertEquals(0, $affected_rows);
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'            , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ----------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III'  ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Alta Hegmann(U)' ,        2 , '2003-02-17' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
                [        3 , 'Damien Kling'    ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $user->name    = "Update 1";
        $user->gender  = 1;
        $affected_rows = Dao::update('users', $user, 'user_id = :user_id', [
            'include' => ['name']
        ]);

        $this->assertEquals(1, $affected_rows);
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'            , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ----------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III'  ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Update 1'        ,        2 , '2003-02-17' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
                [        3 , 'Damien Kling'    ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $user->name    = "Update 2";
        $user->gender  = 1;
        $affected_rows = Dao::update('users', $user, 'user_id = :user_id', [
            'exclude' => ['name']
        ]);

        $this->assertEquals(1, $affected_rows);
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'            , 'gender' , 'birthday'   , 'email'                , 'role' , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ----------------- | -------- | ------------ | ---------------------- | ------ | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III'  ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user' , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Update 1'        ,        1 , '2003-02-17' , 'alta_h@s2.sflf.local' , 'user' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
                [        3 , 'Damien Kling'    ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'user' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
            ],
            'articles' => [],
        ]);

        $affected_rows = Dao::update('users', ['role' => 'admin', 'updated_at' => $now], 'gender = :gender', [
            'where' => [
                'gender' => 1
            ]
        ]);

        $this->assertEquals(2, $affected_rows);
        $this->assertDatabaseMatches([
            'users' => [
                ['user_id' , 'name'            , 'gender' , 'birthday'   , 'email'                , 'role'  , 'password'                                                     , 'created_at'          , 'updated_at' ],
                // ------- | ----------------- | -------- | ------------ | ---------------------- | ------- | -------------------------------------------------------------- | --------------------- | ------------ |  //
                [        1 , 'Elody Bode III'  ,        2 , '1990-01-08' , 'elody@s1.sflf.local'  , 'user'  , '$2y$10$iUQ0l38dqjdf.L7OeNpyNuzmYf5qPzXAUwyKhC3G0oqTuUAO5ouci' , '2020-01-01 12:34:56' , null         ], // password-{user_id}
                [        2 , 'Update 1'        ,        1 , '2003-02-17' , 'alta_h@s2.sflf.local' , 'admin' , '$2y$10$xpouw11HAUb3FAEBXYcwm.kcGmF0.FetTqkQQJFiShY2TiVCwEAQW' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
                [        3 , 'Damien Kling'    ,        1 , '1992-10-17' , 'damien@s0.sflf.local' , 'admin' , '$2y$10$ciYenJCNJh/rKRy9GRNTIO5HQwP0N2t0Hb5db2ESj8Veaty/TjJCe' , '2020-01-01 12:34:56' , $now         ], // password-{user_id}
            ],
            'articles' => [],
        ]);
    }

    public function test_compile_and_Query()
    {
        $query = Dao::compile(
            "SELECT * FROM i=:i AND d=:d AND s=:s AND t=:t AND a IN (:a) AND ss=:s AND baz=:baz",
            [
                "i"    => 123,
                "d"    => 12.3,
                "s"    => "foo",
                "t"    => new DateTime("2023-01-02 12:34:56"),
                "a"    => [456, 45.6, "bar", new DateTime("2023-03-04 12:34:56")],
                ":baz" => "baz" // Deprecated
            ]
        );
        $this->assertEquals(
            "SELECT * FROM i=?/*0*/ AND d=?/*1*/ AND s=?/*2*/ AND t=?/*3*/ AND a IN (?/*4*/, ?/*5*/, ?/*6*/, ?/*7*/) AND ss=?/*8*/ AND baz=?/*9*/",
            $query->sql()
        );
        $this->assertEquals(
            [123, 12.3, "foo", "2023-01-02 12:34:56", 456, 45.6, "bar", "2023-03-04 12:34:56", "foo", "baz"],
            $query->params()
        );
        $this->assertEquals(
            "idssidssss",
            $query->bindParamTypes()
        );
        $this->assertEquals(
            "/* Emulated SQL */ SELECT * FROM i=123 AND d=12.3 AND s='foo' AND t='2023-01-02 12:34:56' AND a IN (456, 45.6, 'bar', '2023-03-04 12:34:56') AND ss='foo' AND baz='baz'",
            $query->emulate()
        );
    }

    public function test_PageInfo()
    {
        foreach ([
            [[ 1, 10,   0, 5], [ 1,  1,   0,   0,  true, false,  true, false, false,  true, [1                ]]],
            [[ 1, 10,  34, 5], [ 1,  4,   0,   9, false,  true,  true, false,  true, false, [1,  2,  3,  4    ]]],
            [[ 1, 10, 123, 5], [ 1, 13,   0,   9, false,  true,  true, false,  true, false, [1,  2,  3,  4,  5]]],
            [[ 4, 10,  34, 5], [ 4,  4,  30,  33, false,  true, false,  true, false,  true, [1,  2,  3,  4    ]]],
            [[ 4, 10, 123, 5], [ 4, 13,  30,  39, false,  true, false,  true,  true, false, [2,  3,  4,  5,  6]]],
            [[ 4, 10, 123, 3], [ 4, 13,  30,  39, false,  true, false,  true,  true, false, [3,  4,  5        ]]],
            [[20, 10,  34, 5], [ 4,  4,  30,  33, false,  true, false,  true, false,  true, [1,  2,  3,  4    ]]],
            [[20, 10, 123, 5], [13, 13, 120, 122, false,  true, false,  true, false,  true, [9, 10, 11, 12, 13]]],
            [[-3, 10,  34, 5], [ 1,  4,   0,   9, false,  true,  true, false,  true, false, [1,  2,  3,  4    ]]],
            [[-3, 10, 123, 5], [ 1, 13,   0,   9, false,  true,  true, false,  true, false, [1,  2,  3,  4,  5]]],
            [[ 4, 15, 123, 5], [ 4,  9,  45,  59, false,  true, false,  true,  true, false, [2,  3,  4,  5,  6]]],
            [[ 8, 15, 123, 5], [ 8,  9, 105, 119, false,  true, false,  true,  true, false, [5,  6,  7,  8,  9]]],
            [[ 1, 50,  34, 5], [ 1,  1,   0,  33, false, false,  true, false, false,  true, [1                ]]],
        ] as [
            [$page, $page_size, $hit_count, $neighbor_size],
            [$actual_page, $max_page, $offset, $limit, $is_empty, $is_multi_page, $is_first_page, $has_prev_page, $has_next_page, $is_last_page, $neighbor_pages]
        ]) {
            $pi = new PageInfo($page, $page_size, $hit_count);
            $this->assertEquals($actual_page, $pi->page);
            $this->assertEquals($page_size, $pi->page_size);
            $this->assertEquals($hit_count, $pi->hit_count);
            $this->assertEquals($max_page, $pi->max_page);
            $this->assertEquals($offset, $pi->offset);
            $this->assertEquals($limit, $pi->limit);
            $this->assertEquals($is_empty, $pi->isEmpty());
            $this->assertEquals($is_multi_page, $pi->isMultiPage());
            $this->assertEquals($is_first_page, $pi->isFirstPage());
            $this->assertEquals($has_prev_page, $pi->hasPrevPage());
            $this->assertEquals($has_next_page, $pi->hasNextPage());
            $this->assertEquals($is_last_page, $pi->isLastPage());
            $this->assertEquals($neighbor_pages, $pi->getNeighborPages($neighbor_size));
        }
    }
}
