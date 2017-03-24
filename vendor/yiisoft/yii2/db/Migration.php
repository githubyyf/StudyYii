<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\Component;
use yii\di\Instance;

/**
 * 迁移脚本是代表一个数据库迁移脚本的基类
 * Migration is the base class for representing a database migration.
 *
 * 迁移脚本的设计是被用于"yii migrate"的命令
 * Migration is designed to be used together with the "yii migrate" command.
 *
 * 每一个迁移脚本的子类代表一个数据库的迁移脚步个体（通过子类的名称进行标识和区分），
 * Each child class of Migration represents an individual（个体） database migration which
 * is identified by the child class name.
 *
 * 在每个迁移脚本中，[[up()]]方法应该被隐式的包含了"upgrading"（执行）数据库的逻辑。
 * [[down()]]方法是"downgrading"（回滚）的逻辑。"yii migrate"命令在一个应用程序中管理所有可用的迁移
 * Within（在） each migration, the [[up()]] method should be overridden to contain the logic（逻辑）
 * for "upgrading" the database; while the [[down()]] method for the "downgrading"
 * logic. The "yii migrate" command manages（管理） all available migrations in an application.
 *
 * 如果数据库提供事务，你也会覆盖[[safeUp()]]和[[safeDown()]]，以至于如果有任何的错误在upgrading 或者 downgrading发生，
 * 所有的迁移脚本回立即恢复，
 * If the database supports transactions（事务）, you may also override [[safeUp()]] and
 * [[safeDown()]] so that if anything wrong happens during the upgrading or downgrading,
 * the whole migration can be reverted（恢复） in a whole.
 *
 * 迁移脚本提供了一系列的简单的方法操纵数据库的数据和模式。
 * 例如，[[insert()]]方法可以被简单的插入一条数据到数据库的表中，[[createTable()]]方法可以用于创建表。
 * 相对于命令行执行的脚本中同样的方法，这些方法会有显示一些扩展的方面在参数和执行时间方面，
 * Migration provides a set of convenient（方便） methods for manipulating（操纵） database data and schema（模式）.
 * For example, the [[insert()]] method can be used to easily insert a row of data into
 * a database table; the [[createTable()]] method can be used to create a database table.
 * Compared with the same methods in [[Command]], these methods will display extra
 * information（信息） showing the method parameters and execution time, which may be useful when
 * applying migrations.
 * 关于迁移脚本更多的细节和使用信息，请参考【迁移脚本指导文章】(guide:db-migrations)
 * For more details and usage（使用） information on Migration, see the [guide article on Migration](guide:db-migrations).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Migration extends Component implements MigrationInterface
{
    use SchemaBuilderTrait;

    /**
     * @var Connection|array|string 数据库连接对象或者数据库连接的应用程序组件ID应该和迁移脚本一起工作。
     * 自从版本2.0.2，可以通过配置文件数组创建对象。
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection
     * that this migration should work with. Starting from version 2.0.2, this can also be a configuration array
     * for creating the object.
     * 注意，当迁移脚本对象通过迁移脚本命令创建，这个项目会被命令行重写，如果你不想通过使用通过命令行提供的数据库连接，
     * 你可以重写[[init()]]方法，格式如下：
     * Note that when a Migration object is created by the `migrate` command, this property（性能） will be overwritten
     * by the command. If you do not want to use the DB connection provided by the command, you may override
     * the [[init()]] method like the following:
     *
     * ```php
     * public function init()
     * {
     *     $this->db = 'db2';
     *     parent::init();
     * }
     * ```
     */
    public $db = 'db';


    /**
     * 初始化迁移脚本
     * 如果[[db]]是`null`这个方法将会设置[[db]]为'db'应用组件，
     * Initializes the migration.
     * This method will set [[db]] to be the 'db' application component, if it is `null`.
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
        $this->db->getSchema()->refresh();
        $this->db->enableSlaves = false;
    }

    /**
     * @inheritdoc
     * @since 2.0.6
     */
    protected function getDb()
    {
        return $this->db;
    }

    /**
     * 这个方法包含的逻辑在使用迁移脚本的时候被扩展。
     * This method contains the logic to be executed when applying this migration.
     * Child classes may override this method to provide actual migration logic.
     * @return bool return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function up()
    {
        $transaction = $this->db->beginTransaction();
        try {
            if ($this->safeUp() === false) {
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $this->printException($e);
            $transaction->rollBack();
            return false;
        } catch (\Throwable $e) {
            $this->printException($e);
            $transaction->rollBack();
            return false;
        }

        return null;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * The default implementation throws an exception indicating the migration cannot be removed.
     * Child classes may override this method if the corresponding migrations can be removed.
     * @return bool return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function down()
    {
        $transaction = $this->db->beginTransaction();
        try {
            if ($this->safeDown() === false) {
                $transaction->rollBack();
                return false;
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $this->printException($e);
            $transaction->rollBack();
            return false;
        } catch (\Throwable $e) {
            $this->printException($e);
            $transaction->rollBack();
            return false;
        }

        return null;
    }

    /**
     * @param \Throwable|\Exception $e
     */
    private function printException($e)
    {
        echo 'Exception: ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ")\n";
        echo $e->getTraceAsString() . "\n";
    }

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     * @return bool return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     * @return bool return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
    }

    /**
     * Executes a SQL statement.
     * This method executes the specified SQL statement using [[db]].
     * @param string $sql the SQL statement to be executed
     * @param array $params input parameters (name => value) for the SQL execution.
     * See [[Command::execute()]] for more details.
     */
    public function execute($sql, $params = [])
    {
        echo "    > execute SQL: $sql ...";
        $time = microtime(true);
        $this->db->createCommand($sql)->bindValues($params)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Creates and executes an INSERT SQL statement.
     * The method will properly escape the column names, and bind the values to be inserted.
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     */
    public function insert($table, $columns)
    {
        echo "    > insert into $table ...";
        $time = microtime(true);
        $this->db->createCommand()->insert($table, $columns)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Creates and executes an batch INSERT SQL statement.
     * The method will properly escape the column names, and bind the values to be inserted.
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param array $rows the rows to be batch inserted into the table
     */
    public function batchInsert($table, $columns, $rows)
    {
        echo "    > insert into $table ...";
        $time = microtime(true);
        $this->db->createCommand()->batchInsert($table, $columns, $rows)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Creates and executes an UPDATE SQL statement.
     * The method will properly escape the column names and bind the values to be updated.
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the conditions that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     */
    public function update($table, $columns, $condition = '', $params = [])
    {
        echo "    > update $table ...";
        $time = microtime(true);
        $this->db->createCommand()->update($table, $columns, $condition, $params)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Creates and executes a DELETE SQL statement.
     * @param string $table the table where the data will be deleted from.
     * @param array|string $condition the conditions that will be put in the WHERE part. Please
     * refer to [[Query::where()]] on how to specify conditions.
     * @param array $params the parameters to be bound to the query.
     */
    public function delete($table, $condition = '', $params = [])
    {
        echo "    > delete from $table ...";
        $time = microtime(true);
        $this->db->createCommand()->delete($table, $condition, $params)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for creating a new DB table.
     *
     * The columns in the new  table should be specified as name-definition pairs (e.g. 'name' => 'string'),
     * where name stands for a column name which will be properly quoted by the method, and definition
     * stands for the column type which can contain an abstract DB type.
     *
     * The [[QueryBuilder::getColumnType()]] method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly
     * put into the generated SQL.
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string $options additional SQL fragment that will be appended to the generated SQL.
     */
    public function createTable($table, $columns, $options = null)
    {
        echo "    > create table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->createTable($table, $columns, $options)->execute();
        foreach ($columns as $column => $type) {
            if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
                $this->db->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
            }
        }
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for renaming a DB table.
     * @param string $table the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     */
    public function renameTable($table, $newName)
    {
        echo "    > rename table $table to $newName ...";
        $time = microtime(true);
        $this->db->createCommand()->renameTable($table, $newName)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for dropping a DB table.
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     */
    public function dropTable($table)
    {
        echo "    > drop table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->dropTable($table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for truncating a DB table.
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     */
    public function truncateTable($table)
    {
        echo "    > truncate table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->truncateTable($table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for adding a new DB column.
     * @param string $table the table that the new column will be added to. The table name will be properly quoted by the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param string $type the column type. The [[QueryBuilder::getColumnType()]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     */
    public function addColumn($table, $column, $type)
    {
        echo "    > add column $column $type to table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->addColumn($table, $column, $type)->execute();
        if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
        }
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for dropping a DB column.
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     */
    public function dropColumn($table, $column)
    {
        echo "    > drop column $column from table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->dropColumn($table, $column)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for renaming a column.
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     */
    public function renameColumn($table, $name, $newName)
    {
        echo "    > rename column $name in table $table to $newName ...";
        $time = microtime(true);
        $this->db->createCommand()->renameColumn($table, $name, $newName)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for changing the definition of a column.
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The [[QueryBuilder::getColumnType()]] method will be invoked to convert abstract column type (if any)
     * into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     */
    public function alterColumn($table, $column, $type)
    {
        echo "    > alter column $column in table $table to $type ...";
        $time = microtime(true);
        $this->db->createCommand()->alterColumn($table, $column, $type)->execute();
        if ($type instanceof ColumnSchemaBuilder && $type->comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $type->comment)->execute();
        }
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for creating a primary key.
     * The method will properly quote the table and column names.
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param string|array $columns comma separated string or array of columns that the primary key will consist of.
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        echo "    > add primary key $name on $table (" . (is_array($columns) ? implode(',', $columns) : $columns) . ') ...';
        $time = microtime(true);
        $this->db->createCommand()->addPrimaryKey($name, $table, $columns)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for dropping a primary key.
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     */
    public function dropPrimaryKey($name, $table)
    {
        echo "    > drop primary key $name ...";
        $time = microtime(true);
        $this->db->createCommand()->dropPrimaryKey($name, $table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table.
     * The method will properly quote the table and column names.
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param string|array $columns the name of the column to that the constraint will be added on. If there are multiple columns, separate them with commas or use an array.
     * @param string $refTable the table that the foreign key references to.
     * @param string|array $refColumns the name of the column that the foreign key references to. If there are multiple columns, separate them with commas or use an array.
     * @param string $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     * @param string $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION, SET DEFAULT, SET NULL
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        echo "    > add foreign key $name: $table (" . implode(',', (array) $columns) . ") references $refTable (" . implode(',', (array) $refColumns) . ') ...';
        $time = microtime(true);
        $this->db->createCommand()->addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete, $update)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     */
    public function dropForeignKey($name, $table)
    {
        echo "    > drop foreign key $name from table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->dropForeignKey($name, $table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for creating a new index.
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by the method.
     * @param string|array $columns the column(s) that should be included in the index. If there are multiple columns, please separate them
     * by commas or use an array. Each column name will be properly quoted by the method. Quoting will be skipped for column names that
     * include a left parenthesis "(".
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        echo '    > create' . ($unique ? ' unique' : '') . " index $name on $table (" . implode(',', (array) $columns) . ') ...';
        $time = microtime(true);
        $this->db->createCommand()->createIndex($name, $table, $columns, $unique)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and executes a SQL statement for dropping an index.
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     */
    public function dropIndex($name, $table)
    {
        echo "    > drop index $name on $table ...";
        $time = microtime(true);
        $this->db->createCommand()->dropIndex($name, $table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and execute a SQL statement for adding comment to column
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     * @since 2.0.8
     */
    public function addCommentOnColumn($table, $column, $comment)
    {
        echo "    > add comment on column $column ...";
        $time = microtime(true);
        $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds a SQL statement for adding comment to table
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     * @since 2.0.8
     */
    public function addCommentOnTable($table, $comment)
    {
        echo "    > add comment on table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->addCommentOnTable($table, $comment)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds and execute a SQL statement for dropping comment from column
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the method.
     * @since 2.0.8
     */
    public function dropCommentFromColumn($table, $column)
    {
        echo "    > drop comment from column $column ...";
        $time = microtime(true);
        $this->db->createCommand()->dropCommentFromColumn($table, $column)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }

    /**
     * Builds a SQL statement for dropping comment from table
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the method.
     * @since 2.0.8
     */
    public function dropCommentFromTable($table)
    {
        echo "    > drop comment from table $table ...";
        $time = microtime(true);
        $this->db->createCommand()->dropCommentFromTable($table)->execute();
        echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }
}
