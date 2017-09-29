<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\data;

use yii\helpers\ArrayHelper;

/**
 * ArrayDataProvider 实现了一个基于数据数组的数据提供者。
 * ArrayDataProvider implements a data provider based on a data array.
 *
 * [所有model]属性包含所有的数据模型，可以进行排序 和\或 分页
 * The [[allModels]] property contains all data models that may be sorted and/or paginated.
 * ArrayDataProvider 会提供排序或者分页后的数据。
 * ArrayDataProvider will provide the data after sorting and/or pagination.
 * 你可以根据自己的需要，配置【排序】和【分页】这属于自定义排序和分页的行为。
 * You may configure the [[sort]] and [[pagination]] properties to
 * customize the sorting and pagination behaviors.
 *
 * 元素在[[所有的模型]]的数组也是对象或者是关联数组。
 * Elements in the [[allModels]] array may be either objects (e.g. model objects)
 * or associative arrays (e.g. query results of DAO).
 * 请确保将[键]属性设置为唯一标识数据记录的字段的名称，如果没有这样的字段，则将其设置为false。
 * Make sure to set the [[key]] property to the name of the field that uniquely
 * identifies a data record or false if you do not have such a field.
 *相比ActiveDataProvider，ArrayDataProvider可能不那么有效因为它需要有[ ] [所有]准备
 * Compared to [[ActiveDataProvider]], ArrayDataProvider could be less efficient
 * because it needs to have [[allModels]] ready.
 *ArrayDataProvider 可能会用如下的方式：
 * ArrayDataProvider may be used in the following way:
 *
 * ```php
 * $query = new Query;
 * $provider = new ArrayDataProvider([
 *     'allModels' => $query->from('post')->all(),
 *     'sort' => [
 *         'attributes' => ['id', 'username', 'email'],
 *     ],
 *     'pagination' => [
 *         'pageSize' => 10,
 *     ],
 * ]);
 * //获取当前页
 * // get the posts in the current page
 * $posts = $provider->getModels();
 * ```
 *注意： 如果要使用排序功能，则必须配置[排序]属性，以便提供程序知道哪些列可以排序。
 * Note: if you want to use the sorting feature, you must configure the [[sort]] property
 * so that the provider knows which columns can be sorted.
 *
 * 更多的细节和ArrayDataProvider使用信息，看到数据提供者]的[指南文章](guide:output-data-providers)
 * For more details and usage information on ArrayDataProvider, see the [guide article on data providers](guide:output-data-providers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ArrayDataProvider extends BaseDataProvider
{
    /**
     * 字符串|回调函数 是作为数据模型的主键。
     * @var string|callable the column that is used as the key of the data models.
     *                      这也是一个列的名称，或者一个回调函数，返回值是数据模型的一个主键。
     * This can be either a column name, or a callable that returns the key value of a given data model.
     *                      如果没有设置，将会使用【模型】数组。
     * If this is not set, the index of the [[models]] array will be used.
     * @see getKeys()
     */
    public $key;
    /**
     *那不是分页或排序数据。当启用了分页。---获取去的所有数据的对象
     * @var array the data that is not paginated or sorted. When pagination is enabled,
     *            此属性通常包含比[模型]更多的元素
     * this property usually contains more elements than [[models]].
     *数组元素必须使用基于零的整数键。
     * The array elements must use zero-based integer keys.
     */
    public $allModels;
    /**
     * 字符串名称的[ \yii\base\Model|Model]类，将代表。
     * @var string the name of the [[\yii\base\Model|Model]] class that will be represented.
     *             此属性用于获取列的名称。
     * This property is used to get columns' names.
     * @since 2.0.9
     */
    public $modelClass;


    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        //如果allModels是空，则返回空数组，
        if (($models = $this->allModels) === null) {
            return [];
        }

        //判断使用使用了排序字段
        if (($sort = $this->getSort()) !== false) {
            $models = $this->sortModels($models, $sort);
        }

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();

            if ($pagination->getPageSize() > 0) {
                $models = array_slice($models, $pagination->getOffset(), $pagination->getLimit(), true);
            }
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    protected function prepareKeys($models)
    {
        if ($this->key !== null) {
            $keys = [];
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }

            return $keys;
        } else {
            return array_keys($models);
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        return count($this->allModels);
    }

    /**
     * 排序数据，根据提供的排序定义。
     * Sorts the data models according to the given sort definition
     * @param array $models 被排序的模型 the models to be sorted
     * @param Sort $sort 排序规则 the sort definition
     * @return array the sorted data models 排序后的数据
     */
    protected function sortModels($models, $sort)
    {
        $orders = $sort->getOrders();
        if (!empty($orders)) {
            ArrayHelper::multisort($models, array_keys($orders), array_values($orders));
        }

        return $models;
    }
}
