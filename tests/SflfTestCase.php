<?php
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * 基底テストケース
 */
abstract class SflfTestCase extends TestCase
{
    /**
     * @var Query[] executed SQL queries
     */
    private static $executed_queries = [];

    /**
     * Dao初期化処理
     *
     * @return void
     */
    public static function setUpDatabase() : void
    {
        Dao::connect('mariadb', 'sflf', 'sflf', 'sflf', null, function ($sql) {
            static::$executed_queries[] = $sql ;
        });
        static::clearExecutedQueries();
    }

    /**
     * Dao終了処理
     *
     * @return void
     */
    public static function tearDownDatabase() : void
    {
        Dao::close();
        static::clearExecutedQueries();
    }

    /**
     * Setup dataset by given data.
     * This method is expected to be called in setUp() or each test method.
     * The data format is
     * [
     *    'table_name' => [
     *        ['col1', 'col2', ...], // header columns section
     *        [1     , 'foo' , ...], // data section
     *        [2     , 'bar' , ...],
     *        ...
     *    ],
     *    'table_name_2' => null,    // define value as null or [] if you want to just truncate table ($with_truncate = true)
     * ]
     *
     * @param array $data for insert into tables
     * @param bool $with_truncate (default: true)
     * @return void
     */
    public static function setUpDataSet(array $data, bool $with_truncate = true) : void
    {
        Dao::begin();
        foreach (array_keys($data) as $table_name) {
            if ($with_truncate) {
                Dao::truncate($table_name, true);
            }
            $records           = $data[$table_name] ?? [];
            $columns           = array_shift($records) ?? [];
            $quoted_table_name = Dao::quoteIdentifier($table_name);
            foreach ($records as $record) {
                Dao::queryAffect("INSERT INTO {$quoted_table_name} (". join(',', array_map(function ($v) { return Dao::quoteIdentifier($v); }, $columns)).") VALUES (:values)", ['values' => $record]);
            }
        }
        Dao::commit();
        static::clearExecutedQueries();
    }

    /**
     * Dump all executed queries.
     *
     * @return void
     */
    public static function dumpExecutedQueries() : void
    {
        echo "\n";
        echo "---------- [ Executed Queries ] ----------\n";
        foreach (static::$executed_queries as $i => $query) {
            echo "[{$i}] >> {$query}\n";
        }
        echo "------------------------------------------\n";
    }

    /**
     * Clear executed query.
     *
     * @return void
     */
    public static function clearExecutedQueries() : void
    {
        static::$executed_queries = [];
    }

    /**
     * Dequeue oldest executed query.
     *
     * @return Query|null
     */
    public static function dequeueExecutedQuery() : ?Query
    {
        return array_shift(static::$executed_queries);
    }

    // ========================================================================
    // Dependent Rebet\Tools\Testable\TestHelper methods and assertions
    // ========================================================================

    /**
     * @see Rebet\Tools\Testable\TestHelper::success
     */
    public static function success() : void
    {
        static::assertTrue(true);
    }

    /**
     * Asserts that two string variables are equal.
     *
     * @param string $expect
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringEquals(string $expect, string $actual, string $message = '') : void
    {
        static::assertEquals($expect, $actual, $message);
    }

    /**
     * Asserts that two string variables are not equal.
     * If more than one expected value is given, it states that it is not equals any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotEqualsAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertNotEquals($expect, $actual, $message);
        }
    }

    /**
     * Asserts that each two string variables are equal.
     *
     * @param string[] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringEqualsEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringEquals($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string contains expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringContainsAll($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertStringContainsString($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an actual string does not contains expects.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotContainsAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertStringNotContainsString($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an each actual string contains each expects.
     * If more than one expected value is given, it states that it is contains all of them.
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringContainsEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringContainsAll($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string matches expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringRegExpAll($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertMatchesRegularExpression($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an actual string does not match expected regular expressions.
     * If more than one expected value is given, it states that it is not contains any of them.
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotRegExpAny($expects, string $actual, string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        foreach ($expects as $expect) {
            static::assertDoesNotMatchRegularExpression($expect, $actual, $message);
        }
    }

    /**
     * Asserts that an each actual string matches each expected regular expressions.
     * If more than one expected value is given, it states that it matches all of them.
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringRegExpEach(array $expects, array $actuals, string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringRegExpAll($expect, $actuals[$i], $message);
        }
    }

    /**
     * Asserts that an actual string matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringWildcardAll($expects, string $actual, array $wildcards = [], string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        $message = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $expect) {
            foreach ($wildcards as $real => $alias) {
                $expect = addcslashes($expect, $real);
                $expect = str_replace($alias, $real, $expect);
            }
            if (!\fnmatch($expect, $actual)) {
                static::fail("{$message}Failed asserting that wildcard match: expect \"{$expect}\" but actual \"{$actual}\".");
            }
        }
        static::success();
    }

    /**
     * Asserts that an actual string matches expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string|string[] $expects
     * @param string $actual
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringNotWildcardAny($expects, string $actual, array $wildcards = [], string $message = '') : void
    {
        $expects = is_array($expects) ? $expects : [$expects] ;
        $message = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $expect) {
            foreach ($wildcards as $real => $alias) {
                $expect = addcslashes($expect, $real);
                $expect = str_replace($alias, $real, $expect);
            }
            if (\fnmatch($expect, $actual)) {
                static::fail("{$message}Failed asserting that wildcard not match: not expect \"{$expect}\" but actual \"$actual\".");
            }
        }
        static::success();
    }

    /**
     * Asserts that an each actual string matches each expected wildcards.
     * If more than one expected value is given, it states that it matches all of them.
     * @see fnmatch()
     *
     * @param string[]|string[][] $expects
     * @param string[] $actuals
     * @param string[] $wildcards aliases definition ['real' => 'alias', ...] for example ['*' => '@'] means '@ *strong* @' become '* \*strong\* *' (default: [])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertStringWildcardEach(array $expects, array $actuals, array $wildcards = [], string $message = '') : void
    {
        static::assertEquals(count($expects), count($actuals), $message);
        foreach ($expects as $i => $expect) {
            static::assertStringWildcardAll($expect, $actuals[$i], $wildcards, $message);
        }
    }

    // ========================================================================
    // Extended assertions
    // ========================================================================

    /**
     * Asserts that an executed SQL matches expected wildcards aliased ['*' => '@'].
     * This assertion checks emulated SQL but it will trim mark comment of '/站・Emulated SQL 站・ '.
     * So you can JUST check SQL like "SELECT * FROM table_name WHERE col = 'value'".
     *
     * @param string $expect SQL for general drivers
     * @param array $wildcards (default: ['*' => '@'])
     * @param string $message (default: '')
     * @return void
     */
    public static function assertExecutedQueryWildcard(string $expect, array $wildcards = ['*' => '@'], string $message = '') : void
    {
        static::assertStringWildcardAll(
            $expect,
            static::dequeueExecutedQuery()->emulate(),
            $wildcards,
            $message
        );
    }

    /**
     * Asserts that database records matches.
     *
     * The expects data format is
     * [
     *    'table_name' => [
     *        ['col1', 'col2', ...], // Header columns section
     *        [1     , 'foo' , ...], // Data section
     *        [2     , 'bar' , ...],
     *        ...
     *    ],
     *    'table_name_2' => null,    // Define value as null or [] if you want to check data is not exists.
     * ]
     *
     * NOTE: Expect data MUST be included primary keys.
     *
     * @param array $expects data (MUST be included primary keys)
     * @param bool $strict if true then check rows count are same. (default: true)
     * @param string $message (default: '')
     * @return void
     */
    public static function assertDatabaseMatches(array $expects, bool $strict = true, string $message = '') : void
    {
        $cloner   = new VarCloner();
        $dumper   = new CliDumper();
        $message  = empty($message) ? $message : "{$message}\n" ;
        foreach ($expects as $table_name => $rows) {
            $columns = array_shift($rows) ?? [];
            $table   = Dao::quoteIdentifier($table_name);
            if ($rows != array_unique($rows, SORT_REGULAR)) {
                static::fail("{$message}Failed asserting that table '{$table_name}' duplicate expect data were contains.");
            }
            if ($strict) {
                $actual_count = Dao::count("SELECT * FROM {$table}");
                $expect_count = count($rows);
                if ($expect_count != $actual_count) {
                    static::fail(
                        "{$message}Failed asserting that table '{$table_name}' rows count: expect \"{$expect_count}\" but actual \"{$actual_count}\".\n".
                        "\n".
                        "---------- [ Full data of {$table_name} ] ----------\n".
                        $dumper->dump($cloner->cloneVar(Dao::select("SELECT * FROM {$table}", [reset($columns) => 'ASC'])), true)."\n".
                        "----------------------------------------------------\n"
                    );
                }
            }
            foreach ($rows as $row) {
                $params = array_combine($columns, $row);
                $sql    = "SELECT * FROM {$table} WHERE 1=1";
                foreach ($params as $column => $value) {
                    $sql .= " AND ".Dao::quoteIdentifier($column).($value === null ? " IS NULL" : " = :{$column}") ;
                }
                if (($count = Dao::count($sql, $params)) != 1) {
                    static::fail(
                        "{$message}Failed asserting that table '{$table_name}' rows ".($count === 0 ? "miss match" : "too many match").": expect \n".
                        $dumper->dump($cloner->cloneVar($params), true)."\n".
                        "but SQL \n".
                        $sql."\n".
                        ($count === 0 ? "was not hit any data.\n" : "was hit {$count} data.\n").
                        "\n".
                        "---------- [ Full data of {$table_name} ] ----------\n".
                        $dumper->dump($cloner->cloneVar(Dao::select("SELECT * FROM {$table}", [reset($columns) => 'ASC'])), true)."\n".
                        "----------------------------------------------------\n"
                    );
                }
            }
        }
        static::success();
    }
}
