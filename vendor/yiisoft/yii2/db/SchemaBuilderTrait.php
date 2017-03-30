<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * 创建概要特性包含：创建[[ColumnSchemaBuilder]]的快捷接口。
 * SchemaBuilderTrait contains shortcut methods to create instances of [[ColumnSchemaBuilder]].
 * 这些PHP接口可以用于数据库迁移脚本定义数据模型的类型。
 * These can be used in database migrations to define database schema types using a PHP interface.
 * 这是很有用的在使用独立的方式定义DBMS（数据库管理系统），这样应用程序可以运行在不同的DBMS以同样的方式
 * This is useful to define a schema in a DBMS（数据库管理系统） independent(独立的) way
 * so that the application（应用程序） may run on different DBMS the same way.
 * 例如,您可能使用下面的代码在你的迁移脚本文件中。
 * For example you may use the following code inside your migration files:
 *
 * ```php
 * $this->createTable('example_table', [
 *   'id' => $this->primaryKey(),
 *   'name' => $this->string(64)->notNull(),
 *   'type' => $this->integer()->notNull()->defaultValue(10),
 *   'description' => $this->text(),
 *   'rule_name' => $this->string(64),
 *   'data' => $this->text(),
 *   'created_at' => $this->datetime()->notNull(),
 *   'updated_at' => $this->datetime(),
 * ]);
 * ```
 *
 * @author Vasenin Matvey <vaseninm@gmail.com>
 * @since 2.0.6
 */
trait SchemaBuilderTrait
{
    /**
     * 连接数据库连接用于模式构建
     * @return Connection the database connection to be used for schema building.
     */
    protected abstract function getDb();

    /**
     * 创建主键列--int(11)
     * Creates a primary key column.
     * @param int $length 列的大小或者精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function primaryKey($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_PK, $length);
    }

    /**
     * 创建大主键列--与主键区别在于 int类型bigint(20)。
     * Creates a big primary key column.
     * @param int $length 列的大小或者精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function bigPrimaryKey($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BIGPK, $length);
    }

    /**
     * 创建字符类型列。
     * Creates a char column.
     * @param int $length 列大小定义即最大字符长度。column size definition i.e. the maximum string length.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.8
     */
    public function char($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_CHAR, $length);
    }

    /**
     * 创建字符串列
     * Creates a string column.
     * @param int $length 列大小定义即最大字符串长度。column size definition i.e. the maximum string length.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function string($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, $length);
    }

    /**
     * 创建文本列
     * Creates a text column.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function text()
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TEXT);
    }

    /**
     * 创建小整型列。
     * Creates a smallint column.
     * @param int $length 列大小或精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function smallInteger($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_SMALLINT, $length);
    }

    /**
     * 创建一个整型列
     * Creates an integer column.
     * @param int $length 列大小或精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function integer($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_INTEGER, $length);
    }

    /**
     * 创建一个大整型
     * Creates a bigint column.
     * @param int $length 列大小或精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function bigInteger($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BIGINT, $length);
    }

    /**
     * 创建浮点类型的列
     * Creates a float column.
     * @param int $precision 列值的精度。第一个参数传递给列类型,如浮点(精度)
     * column value precision. First parameter passed to the column type, e.g. FLOAT(precision).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function float($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_FLOAT, $precision);
    }

    /**
     * 创建双精度列
     * Creates a double column.
     * @param int $precision 列值的精度。第一个参数传递给列类型,如双精度(精度)
     * column value precision. First parameter passed to the column type, e.g. DOUBLE(precision).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function double($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DOUBLE, $precision);
    }

    /**
     * 创建小数列。
     * Creates a decimal column.
     * @param int $precision 列值精度，所有的数据个数。第一个参数传递给列类型,例如十进制(精度、规模)
     * column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @param int $scale 列值范围，通常是小数点后的位数。
     * column value scale, which is usually the number of digits after the decimal point.
     * 第二个参数传递给列类型,例如十进制(精度、规模)。
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function decimal($precision = null, $scale = null)
    {
        $length = [];
        if ($precision !== null) {
            $length[] = $precision;
        }
        if ($scale !== null) {
            $length[] = $scale;
        }
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DECIMAL, $length);
    }

    /**
     * 创建时间日期格式。
     * Creates a datetime column.
     * @param int $precision 列值的精度。第一个参数传递给列类型,例如DATETIME(精度)
     * column value precision. First parameter passed to the column type, e.g. DATETIME(precision).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function dateTime($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DATETIME, $precision);
    }

    /**
     * 创建一个时间戳列。
     * Creates a timestamp column.
     * @param int $precision 列值的精度。第一个参数传递给列类型,例如时间戳(精度)。
     * column value precision. First parameter passed to the column type, e.g. TIMESTAMP(precision).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function timestamp($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TIMESTAMP, $precision);
    }

    /**
     * 创建一个时间列。
     * Creates a time column.
     * @param int $precision 列值的精度。第一个参数传递给列类型,例如时间(精度)。
     * column value precision. First parameter passed to the column type, e.g. TIME(precision).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function time($precision = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TIME, $precision);
    }

    /**
     * 创建日期列
     * Creates a date column.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function date()
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DATE);
    }

    /**
     * 创建二进制列。
     * Creates a binary column.
     * @param int $length 列大小或精确的定义。column size or precision definition.
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function binary($length = null)
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BINARY, $length);
    }

    /**
     * 创建布尔型列。
     * Creates a boolean column.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function boolean()
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BOOLEAN);
    }

    /**
     * 创建金钱类型列。
     * Creates a money column.
     * @param int $precision 列值的精度,通常是数字的总数。第一个参数传递给列类型,例如双精度(精度、规模)。
     * column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * This parameter will be ignored if not supported by the DBMS.
     * @param int $scale 列值范围,通常是小数点后的位数。第二个参数传递给列类型,例如十进制(精度、规模)。
     * 如果DBMS（Data Base Management System）没有提供这个参数将被忽略。
     * column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * This parameter will be ignored if not supported by the DBMS.
     * @return ColumnSchemaBuilder 可以进一步自定义的列实例。the column instance which can be further customized.
     * @since 2.0.6
     */
    public function money($precision = null, $scale = null)
    {
        $length = [];
        if ($precision !== null) {
            $length[] = $precision;
        }
        if ($scale !== null) {
            $length[] = $scale;
        }
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_MONEY, $length);
    }
}
