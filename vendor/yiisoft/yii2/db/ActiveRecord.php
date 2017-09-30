<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace yii\db;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 *  ActiveRecord是代表从对象关系数据的类的基类
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 * ActiveRecord 实现了[Active Record design pattern]
 * Active Record implements the [Active Record design pattern](http://en.wikipedia.org/wiki/Active_record).
 * 活动记录的背后的前提是，一个独特的[[ActiveRecord]]对象是与特定的数据库表中的行的关系。对象的属性是对应表的列
 * The premise behind Active Record is that an individual [[ActiveRecord]] object is associated with a specific
 * row in a database table. The object's attributes are mapped to the columns of the corresponding table.
 * 引用活动记录的属性等价于获取对应表中该列的记录。
 * Referencing an Active Record attribute is equivalent to accessing the corresponding table column for that record.
 * 作为一个例子， 说`Customer`活动记录了是对应`customer`表。
 * As an example, say that the `Customer` ActiveRecord class is associated with the `customer` table.
 *  应该这样解释，类的`name`属性是自动映射到`column`表中的`name`列。
 * This would mean that the class's `name` attribute is automatically mapped to the `name` column in `customer` table.
 * 多亏了活动记录，假设变量`$customer`是`Customer`类型的对象，获取表中某行`name`的值，可以使用表达式`$customer->name`
 * Thanks to Active Record, assuming the variable `$customer` is an object of type `Customer`, to get the value of
 * the `name` column for the table row, you can use the expression `$customer->name`.
 * 在这个例子中，活动记录提供访问存储在数据库中的数据对象接口
 * In this example, Active Record is providing an object-oriented interface for accessing data stored in the database.
 * 但是活动记录提供了比这个更多的功能。
 * But Active Record provides much more functionality than this.
 * 去声明一个活动记录类，你需要继承[[\yii\db\ActiveRecord]]而且实现“tableName“这个方法。
 * To declare an ActiveRecord class you need to extend [[\yii\db\ActiveRecord]] and
 * implement the `tableName` method:
 * 例如：
 * ```php
 * <?php
 *
 * class Customer extends \yii\db\ActiveRecord
 * {
 *     public static function tableName()
 *     {
 *         return 'customer';
 *     }
 * }
 * ```
 *`tableName`方法只是返回数据库中与这个类有关的表的名称。
 * The `tableName` method only has to return the name of the database table associated with the class.
 * 》提示：你也可以使用[Gii code generator](guide:start-gii)生成你的数据库这表的活动记录类
 * > Tip: You may also use the [Gii code generator](guide:start-gii) to generate ActiveRecord classes from your
 * > database tables.
 * 有两种方式获得类的实例：
 * Class instances are obtained in one of two ways:
 * *通过”new“操作，创建一个空的对象。
 * * Using the `new` operator to create a new, empty object
 * *使用方法取现有记录（或记录）从数据库
 * * Using a method to fetch an existing record (or records) from the database
 * 下面是一个例子显示了一些典型的使用ActiveRecord：
 * Below is an example showing some typical usage of ActiveRecord:
 *
 * ```php
 * $user = new User();
 * $user->name = 'Qiang';
 * $user->save();  // a new row is inserted into user table 一个新的行被插入到用户表中。
 *
 * //下面的例子会从库中获取返回”CeBe”的数据
 * // the following will retrieve the user 'CeBe' from the database
 * $user = User::find()->where(['name' => 'CeBe'])->one();
 * //这将从订单表获取相关记录当关系定义
 * // this will get related records from orders table when relation is defined
 * $orders = $user->orders;
 * ```
 * ActiveRecord的更多的细节和使用信息，查看[guide article on ActiveRecord](guide:db-active-record)
 * For more details and usage information on ActiveRecord, see the [guide article on ActiveRecord](guide:db-active-record).
 *
 * @method ActiveQuery hasMany($class, array $link) see [[BaseActiveRecord::hasMany()]] for more info
 * @method ActiveQuery hasOne($class, array $link) see [[BaseActiveRecord::hasOne()]] for more info
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since  2.0
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     *
     * 插入操作。这主要是用在覆盖transactions() ] [ [ ]指定事务操作。
     * The insert operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_INSERT = 0x01;
    /**
     * 更新操作。这主要是用在覆盖transactions() ] [ [ ]指定事务操作
     * The update operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_UPDATE = 0x02;
    /**
     * 删除操作。这主要是用在覆盖transactions() ] [ [ ]指定事务操作。
     * The delete operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_DELETE = 0x04;
    /**
     * 所有的这三种操作：插入，更新，删除。
     * All three operations: insert, update, delete.
     * 这是表达的快捷方式：OP_INSERT | OP_UPDATE | OP_DELETE。
     * This is a shortcut of the expression: OP_INSERT | OP_UPDATE | OP_DELETE.
     */
    const OP_ALL = 0x07;


    /**
     * 加载默认值从数据库表结构
     * Loads default values from database table schema
     *
     * 你可以使用这个方法在创建新记录后加载默认值。
     * You may call this method to load default values after creating a new instance:
     * 例如：
     * ```php
     * // class Customer extends \yii\db\ActiveRecord
     * $customer = new Customer();
     * $customer->loadDefaultValues();
     * ```
     *
     * $skipIfSet 是否应该保留现有的值。这只会设置`null`属性的默认值
     * @param bool $skipIfSet whether existing value should be preserved.
     *                        This will only set defaults for attributes that are `null`.
     *                        模型实例本身
     * @return $this the model instance itself.
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        foreach (static::getTableSchema()->columns as $column) {
            if ($column->defaultValue !== null && (!$skipIfSet || $this->{$column->name} === null)) {
                $this->{$column->name} = $column->defaultValue;
            }
        }
        return $this;
    }

    /**
     * 返回的AR类使用的数据库连接
     * Returns the database connection used by this AR class.
     * 默认情况下，“DB”应用程序组件用作数据库连接。
     * By default, the "db" application component is used as the database connection.
     * 如果你想使用不同的数据库连接，可以重写此方法
     * You may override this method if you want to use a different database connection.
     * @return Connection the database connection used by this AR class. 返回连接数据库的AR类，
     */
    public static function getDb()
    {
        return Yii::$app->getDb();
    }

    /**
     * 创建一个【activequery】实例与一个给定的SQL语句
     * Creates an [[ActiveQuery]] instance with a given SQL statement.
     * 注意，由于SQL语句已经明确知道，调用额外的修改方法（例如：`where()`, `order()`）在创建[[ActiveQuery]]实例的时间没有影响。
     * 然而调用`with()`, `asArray()` or `indexBy()`是有影响的。
     * Note that because the SQL statement is already specified, calling additional
     * query modification methods (such as `where()`, `order()`) on the created [[ActiveQuery]]
     * instance will have no effect. However, calling `with()`, `asArray()` or `indexBy()` is
     * still fine.
     * 例如下面的例子：
     * Below is an example:
     *
     * ```php
     * $customers = Customer::findBySql('SELECT * FROM customer')->all();
     * ```
     *
     * $sql 需要执行的SQL语句
     * @param string $sql    the SQL statement to be executed ，
     *                $params 参数被绑定到SQL语句在执行过程中。
     * @param array  $params parameters to be bound to the SQL statement during execution.
     *                       返回值是新创建的[[ActiveQuery]]对象
     * @return ActiveQuery the newly created [[ActiveQuery]] instance
     */
    public static function findBySql($sql, $params = [])
    {
        $query = static::find();
        $query->sql = $sql;

        return $query->params($params);
    }

    /**
     * 查找ActiveRecord实例通过给定的条件。
     * Finds ActiveRecord instance(s) by the given condition.
     * 这个方法被[[findOne()]] and [[findAll()]]内部调用。
     * This method is internally called by [[findOne()]] and [[findAll()]].
     *  $condition   请参阅【findone()】为这个参数的解释
     * @param mixed $condition please refer to [[findOne()]] for the explanation of this parameter
     *                         返回值是新创建的[[ActiveQueryInterface|ActiveQuery]]实例。
     * @return ActiveQueryInterface the newly created [[ActiveQueryInterface|ActiveQuery]] instance.
     * @throws InvalidConfigException if there is no primary key defined
     * @internal
     */
    protected static function findByCondition($condition)
    {
        $query = static::find();

        if (!ArrayHelper::isAssociative($condition)) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $pk = $primaryKey[0];
                if (!empty($query->join) || !empty($query->joinWith)) {
                    $pk = static::tableName() . '.' . $pk;
                }
                $condition = [$pk => $condition];
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
        }

        return $query->andWhere($condition);
    }

    /**
     * 更新所有的表，通过提供的属性值和条件。
     * Updates the whole table using the provided attribute values and conditions.
     * 例如，将所有status为2的改为status=1
     * For example, to change the status to be 1 for all customers whose status is 2:
     *
     * ```php
     * Customer::updateAll(['status' => 1], 'status = 2');
     * ```
     * 》警告：如果你没有写任何条件，这个方法将更新表中所有的行。
     * > Warning: If you do not specify any condition, this method will update **all** rows in the table.
     *
     * 注意，这个方法不触发任何事假，如果你需要触发[[EVENT_BEFORE_UPDATE]]或者[[EVENT_AFTER_UPDATE]]事件，
     * 你需要先使用[[find()|find]]方法，然后调用逐项调用[[update()]]。
     * 例如此例子的结果相当于上面的例子。
     *
     * Note that this method will not trigger any events. If you need [[EVENT_BEFORE_UPDATE]] or
     * [[EVENT_AFTER_UPDATE]] to be triggered, you need to [[find()|find]] the models first and then
     * call [[update()]] on each of them. For example an equivalent of the example above would be:
     *
     * ```php
     * $models = Customer::find()->where('status = 2')->all();
     * foreach($models as $model) {
     *     $model->status = 1;
     *     $model->update(false); // skipping validation as no user input is involved 跳过验证用户输入是没有涉及
     * }
     * ```
     * 对于一个大的模型，你可以考虑使用[ [ activequery：：each() ] ]保持内存的使用范围
     * For a large set of models you might consider using [[ActiveQuery::each()]] to keep memory usage within limits.
     *
     * $attributes 属性值，（属性名=》属性值），将被修改保存到表中
     * @param array        $attributes attribute values (name-value pairs) to be saved into the table
     *                     $condition 条件，将会被用户UPDATE SQL的筛选条件的一部分。请参阅[ [查询：：where() ] ]如何指定此参数
     * @param string|array $condition  the conditions that will be put in the WHERE part of the UPDATE SQL.
     *                                 Please refer to [[Query::where()]] on how to specify this parameter.
     *                     $params 参数（name = >value）被绑定到查询
     * @param array        $params     the parameters (name => value) to be bound to the query.
     * @return int the number of rows updated 返回被更新的行数。
     */
    public static function updateAll($attributes, $condition = '', $params = [])
    {
        $command = static::getDb()->createCommand();
        $command->update(static::tableName(), $attributes, $condition, $params);

        return $command->execute();
    }

    /**
     * Updates the whole table using the provided counter changes and conditions.
     *
     * For example, to increment all customers' age by 1,
     *
     * ```php
     * Customer::updateAllCounters(['age' => 1]);
     * ```
     *
     * Note that this method will not trigger any events.
     *
     * @param array        $counters  the counters to be updated (attribute name => increment value).
     *                                Use negative values if you want to decrement the counters.
     * @param string|array $condition the conditions that will be put in the WHERE part of the UPDATE SQL.
     *                                Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array        $params    the parameters (name => value) to be bound to the query.
     *                                Do not name the parameters as `:bp0`, `:bp1`, etc., because they are used internally by this method.
     * @return int the number of rows updated
     */
    public static function updateAllCounters($counters, $condition = '', $params = [])
    {
        $n = 0;
        foreach ($counters as $name => $value) {
            $counters[$name] = new Expression("[[$name]]+:bp{$n}", [":bp{$n}" => $value]);
            $n++;
        }
        $command = static::getDb()->createCommand();
        $command->update(static::tableName(), $counters, $condition, $params);

        return $command->execute();
    }

    /**
     * Deletes rows in the table using the provided conditions.
     *
     * For example, to delete all customers whose status is 3:
     *
     * ```php
     * Customer::deleteAll('status = 3');
     * ```
     *
     * > Warning: If you do not specify any condition, this method will delete **all** rows in the table.
     *
     * Note that this method will not trigger any events. If you need [[EVENT_BEFORE_DELETE]] or
     * [[EVENT_AFTER_DELETE]] to be triggered, you need to [[find()|find]] the models first and then
     * call [[delete()]] on each of them. For example an equivalent of the example above would be:
     *
     * ```php
     * $models = Customer::find()->where('status = 3')->all();
     * foreach($models as $model) {
     *     $model->delete();
     * }
     * ```
     *
     * For a large set of models you might consider using [[ActiveQuery::each()]] to keep memory usage within limits.
     *
     * @param string|array $condition the conditions that will be put in the WHERE part of the DELETE SQL.
     *                                Please refer to [[Query::where()]] on how to specify this parameter.
     * @param array        $params    the parameters (name => value) to be bound to the query.
     * @return int the number of rows deleted
     */
    public static function deleteAll($condition = '', $params = [])
    {
        $command = static::getDb()->createCommand();
        $command->delete(static::tableName(), $condition, $params);

        return $command->execute();
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::className(), [get_called_class()]);
    }

    /**
     * Declares the name of the database table associated with this AR class.
     * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
     * with prefix [[Connection::tablePrefix]]. For example if [[Connection::tablePrefix]] is `tbl_`,
     * `Customer` becomes `tbl_customer`, and `OrderItem` becomes `tbl_order_item`. You may override this method
     * if the table is not named after this convention.
     * @return string the table name
     */
    public static function tableName()
    {
        return '{{%' . Inflector::camel2id(StringHelper::basename(get_called_class()), '_') . '}}';
    }

    /**
     * Returns the schema information of the DB table associated with this AR class.
     * @return TableSchema the schema information of the DB table associated with this AR class.
     * @throws InvalidConfigException if the table for the AR class does not exist.
     */
    public static function getTableSchema()
    {
        $tableSchema = static::getDb()
            ->getSchema()
            ->getTableSchema(static::tableName());

        if ($tableSchema === null) {
            throw new InvalidConfigException('The table does not exist: ' . static::tableName());
        }

        return $tableSchema;
    }

    /**
     * Returns the primary key name(s) for this AR class.
     * The default implementation will return the primary key(s) as declared
     * in the DB table that is associated with this AR class.
     *
     * If the DB table does not declare any primary key, you should override
     * this method to return the attributes that you want to use as primary keys
     * for this AR class.
     *
     * Note that an array should be returned even for a table with single primary key.
     *
     * @return string[] the primary keys of the associated database table.
     */
    public static function primaryKey()
    {
        return static::getTableSchema()->primaryKey;
    }

    /**
     * Returns the list of all attribute names of the model.
     * The default implementation will return all column names of the table associated with this AR class.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_keys(static::getTableSchema()->columns);
    }

    /**
     * Declares which DB operations should be performed within a transaction in different scenarios.
     * The supported DB operations are: [[OP_INSERT]], [[OP_UPDATE]] and [[OP_DELETE]],
     * which correspond to the [[insert()]], [[update()]] and [[delete()]] methods, respectively.
     * By default, these methods are NOT enclosed in a DB transaction.
     *
     * In some scenarios, to ensure data consistency, you may want to enclose some or all of them
     * in transactions. You can do so by overriding this method and returning the operations
     * that need to be transactional. For example,
     *
     * ```php
     * return [
     *     'admin' => self::OP_INSERT,
     *     'api' => self::OP_INSERT | self::OP_UPDATE | self::OP_DELETE,
     *     // the above is equivalent to the following:
     *     // 'api' => self::OP_ALL,
     *
     * ];
     * ```
     *
     * The above declaration specifies that in the "admin" scenario, the insert operation ([[insert()]])
     * should be done in a transaction; and in the "api" scenario, all the operations should be done
     * in a transaction.
     *
     * @return array the declarations of transactional operations. The array keys are scenarios names,
     * and the array values are the corresponding transaction operations.
     */
    public function transactions()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function populateRecord($record, $row)
    {
        $columns = static::getTableSchema()->columns;
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                $row[$name] = $columns[$name]->phpTypecast($value);
            }
        }
        parent::populateRecord($record, $row);
    }

    /**
     * Inserts a row into the associated database table using the attribute values of this record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeValidate()]] when `$runValidation` is `true`. If [[beforeValidate()]]
     *    returns `false`, the rest of the steps will be skipped;
     * 2. call [[afterValidate()]] when `$runValidation` is `true`. If validation
     *    failed, the rest of the steps will be skipped;
     * 3. call [[beforeSave()]]. If [[beforeSave()]] returns `false`,
     *    the rest of the steps will be skipped;
     * 4. insert the record into database. If this fails, it will skip the rest of the steps;
     * 5. call [[afterSave()]];
     *
     * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
     * [[EVENT_AFTER_VALIDATE]], [[EVENT_BEFORE_INSERT]], and [[EVENT_AFTER_INSERT]]
     * will be raised by the corresponding methods.
     *
     * Only the [[dirtyAttributes|changed attribute values]] will be inserted into database.
     *
     * If the table's primary key is auto-incremental and is `null` during insertion,
     * it will be populated with the actual value after insertion.
     *
     * For example, to insert a customer record:
     *
     * ```php
     * $customer = new Customer;
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->insert();
     * ```
     *
     * @param bool  $runValidation whether to perform validation (calling [[validate()]])
     *                             before saving the record. Defaults to `true`. If the validation fails, the record
     *                             will not be saved to the database and this method will return `false`.
     * @param array $attributes    list of attributes that need to be saved. Defaults to `null`,
     *                             meaning all attributes that are loaded from DB will be saved.
     * @return bool whether the attributes are valid and the record is inserted successfully.
     * @throws \Exception in case insert failed.
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->isTransactional(self::OP_INSERT)) {
            return $this->insertInternal($attributes);
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $result = $this->insertInternal($attributes);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Inserts an ActiveRecord into DB without considering transaction.
     * @param array $attributes list of attributes that need to be saved. Defaults to `null`,
     *                          meaning all attributes that are loaded from DB will be saved.
     * @return bool whether the record is inserted successfully.
     */
    protected function insertInternal($attributes = null)
    {
        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (($primaryKeys = static::getDb()->schema->insert(static::tableName(), $values)) === false) {
            return false;
        }
        foreach ($primaryKeys as $name => $value) {
            $id = static::getTableSchema()->columns[$name]->phpTypecast($value);
            $this->setAttribute($name, $id);
            $values[$name] = $id;
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }

    /**
     * Saves the changes to this active record into the associated database table.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeValidate()]] when `$runValidation` is `true`. If [[beforeValidate()]]
     *    returns `false`, the rest of the steps will be skipped;
     * 2. call [[afterValidate()]] when `$runValidation` is `true`. If validation
     *    failed, the rest of the steps will be skipped;
     * 3. call [[beforeSave()]]. If [[beforeSave()]] returns `false`,
     *    the rest of the steps will be skipped;
     * 4. save the record into database. If this fails, it will skip the rest of the steps;
     * 5. call [[afterSave()]];
     *
     * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
     * [[EVENT_AFTER_VALIDATE]], [[EVENT_BEFORE_UPDATE]], and [[EVENT_AFTER_UPDATE]]
     * will be raised by the corresponding methods.
     *
     * Only the [[dirtyAttributes|changed attribute values]] will be saved into database.
     *
     * For example, to update a customer record:
     *
     * ```php
     * $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->update();
     * ```
     *
     * Note that it is possible the update does not affect any row in the table.
     * In this case, this method will return 0. For this reason, you should use the following
     * code to check if update() is successful or not:
     *
     * ```php
     * if ($customer->update() !== false) {
     *     // update successful
     * } else {
     *     // update failed
     * }
     * ```
     *
     * @param bool  $runValidation  whether to perform validation (calling [[validate()]])
     *                              before saving the record. Defaults to `true`. If the validation fails, the record
     *                              will not be saved to the database and this method will return `false`.
     * @param array $attributeNames list of attributes that need to be saved. Defaults to `null`,
     *                              meaning all attributes that are loaded from DB will be saved.
     * @return int|false the number of rows affected, or false if validation fails
     *                              or [[beforeSave()]] stops the updating process.
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     *                              being updated is outdated.
     * @throws \Exception in case update failed.
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not updated due to validation error.', __METHOD__);
            return false;
        }

        if (!$this->isTransactional(self::OP_UPDATE)) {
            return $this->updateInternal($attributeNames);
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $result = $this->updateInternal($attributeNames);
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes the table row corresponding to this active record.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeDelete()]]. If the method returns `false`, it will skip the
     *    rest of the steps;
     * 2. delete the record from the database;
     * 3. call [[afterDelete()]].
     *
     * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
     * will be raised by the corresponding methods.
     *
     * @return int|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
     * being deleted is outdated.
     * @throws \Exception in case delete failed.
     */
    public function delete()
    {
        if (!$this->isTransactional(self::OP_DELETE)) {
            return $this->deleteInternal();
        }

        $transaction = static::getDb()->beginTransaction();
        try {
            $result = $this->deleteInternal();
            if ($result === false) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return $result;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Deletes an ActiveRecord without considering transaction.
     * @return int|false the number of rows deleted, or `false` if the deletion is unsuccessful for some reason.
     * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
     * @throws StaleObjectException
     */
    protected function deleteInternal()
    {
        if (!$this->beforeDelete()) {
            return false;
        }

        // we do not check the return value of deleteAll() because it's possible
        // the record is already deleted in the database and thus the method will return 0
        $condition = $this->getOldPrimaryKey(true);
        $lock = $this->optimisticLock();
        if ($lock !== null) {
            $condition[$lock] = $this->$lock;
        }
        $result = static::deleteAll($condition);
        if ($lock !== null && !$result) {
            throw new StaleObjectException('The object being deleted is outdated.');
        }
        $this->setOldAttributes(null);
        $this->afterDelete();

        return $result;
    }

    /**
     * Returns a value indicating whether the given active record is the same as the current one.
     * The comparison is made by comparing the table names and the primary key values of the two active records.
     * If one of the records [[isNewRecord|is new]] they are also considered not equal.
     * @param ActiveRecord $record record to compare to
     * @return bool whether the two active records refer to the same row in the same database table.
     */
    public function equals($record)
    {
        if ($this->isNewRecord || $record->isNewRecord) {
            return false;
        }

        return static::tableName() === $record->tableName() && $this->getPrimaryKey() === $record->getPrimaryKey();
    }

    /**
     * Returns a value indicating whether the specified operation is transactional in the current [[$scenario]].
     * @param int $operation the operation to check. Possible values are [[OP_INSERT]], [[OP_UPDATE]] and [[OP_DELETE]].
     * @return bool whether the specified operation is transactional in the current [[scenario]].
     */
    public function isTransactional($operation)
    {
        $scenario = $this->getScenario();
        $transactions = $this->transactions();

        return isset($transactions[$scenario]) && ($transactions[$scenario] & $operation);
    }
}
