<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace yii\data;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;

/**
 * BaseDataProvider provides a base class that implements the [[DataProviderInterface]].
 *
 * For more details and usage information on BaseDataProvider, see the [guide article on data providers](guide:output-data-providers).
 *
 * @property int              $count      The number of data models in the current page. This property is read-only.
 * @property array            $keys       The list of key values corresponding to [[models]]. Each data model in [[models]] is
 * uniquely identified by the corresponding key value in this array.
 * @property array            $models     The list of data models in the current page.
 * @property Pagination|false $pagination The pagination object. If this is false, it means the pagination is
 * disabled. Note that the type of this property differs in getter and setter. See [[getPagination()]] and
 * [[setPagination()]] for details.
 * @property Sort|bool        $sort       The sorting object. If this is false, it means the sorting is disabled. Note that
 * the type of this property differs in getter and setter. See [[getSort()]] and [[setSort()]] for details.
 * @property int              $totalCount Total number of possible data models.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
abstract class BaseDataProvider extends Component implements DataProviderInterface
{
    /**
     *一个数据提供者中的唯一标识的字符串（主键）
     * @var string an ID that uniquely identifies the data provider among all data providers.
     * 如果同一页面包含两个或多个不同的数据提供者设置这个属性
     * You should set this property if the same page contains two or more different data providers.
     * 否则，the [[pagination]] and [[sort]]可能不正确
     * Otherwise, the [[pagination]] and [[sort]] may not work properly.
     */
    public $id;

    private $_sort;
    private $_pagination;
    private $_keys;
    private $_models;
    private $_totalCount;


    /**
     * Prepares the data models that will be made available in the current page.
     * @return array the available data models
     */
    abstract protected function prepareModels();

    /**
     * Prepares the keys associated with the currently available data models.
     * @param array $models the available data models
     * @return array the keys
     */
    abstract protected function prepareKeys($models);

    /**
     * Returns a value indicating the total number of data models in this data provider.
     * @return int total number of data models in this data provider.
     */
    abstract protected function prepareTotalCount();

    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via
     * [[getModels()]] and [[getKeys()]].
     *
     * This method will be implicitly called by [[getModels()]] and [[getKeys()]] if it has not been called before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare($forcePrepare = false)
    {
        if ($forcePrepare || $this->_models === null) {
            $this->_models = $this->prepareModels();
        }
        if ($forcePrepare || $this->_keys === null) {
            $this->_keys = $this->prepareKeys($this->_models);
        }
    }

    /**
     * Returns the data models in the current page.
     * @return array the list of data models in the current page.
     */
    public function getModels()
    {
        $this->prepare();

        return $this->_models;
    }

    /**
     * Sets the data models in the current page.
     * @param array $models the models in the current page
     */
    public function setModels($models)
    {
        $this->_models = $models;
    }

    /**
     * Returns the key values associated with the data models.
     * @return array the list of key values corresponding to [[models]]. Each data model in [[models]]
     * is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys()
    {
        $this->prepare();

        return $this->_keys;
    }

    /**
     * Sets the key values associated with the data models.
     * @param array $keys the list of key values corresponding to [[models]].
     */
    public function setKeys($keys)
    {
        $this->_keys = $keys;
    }

    /**
     * Returns the number of data models in the current page.
     * @return int the number of data models in the current page.
     */
    public function getCount()
    {
        return count($this->getModels());
    }

    /**
     * Returns the total number of data models.
     * When [[pagination]] is false, this returns the same value as [[count]].
     * Otherwise, it will call [[prepareTotalCount()]] to get the count.
     * @return int total number of possible data models.
     */
    public function getTotalCount()
    {
        if ($this->getPagination() === false) {
            return $this->getCount();
        } elseif ($this->_totalCount === null) {
            $this->_totalCount = $this->prepareTotalCount();
        }

        return $this->_totalCount;
    }

    /**
     * Sets the total number of data models.
     * @param int $value the total number of data models.
     */
    public function setTotalCount($value)
    {
        $this->_totalCount = $value;
    }

    /**
     * 返回数据提供者的分页对象。
     * Returns the pagination object used by this data provider.
     * 注意：可以通过调用[[prepare()]] or [[getModels()]]获取当前of [[Pagination::totalCount]] and [[Pagination::pageCount]]的值
     * Note that you should call [[prepare()]] or [[getModels()]] first to get correct values
     * of [[Pagination::totalCount]] and [[Pagination::pageCount]].
     *返回分页对象，或者false,如果返回false表示分页对象禁用。
     * @return Pagination|false the pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination()
    {
        if ($this->_pagination === null) {
            $this->setPagination([]);
        }

        return $this->_pagination;
    }

    /**
     * 为数据提供者设置分页。
     * Sets the pagination for this data provider.
     * $value  配用于数据提供者分页的配置
     * @param array|Pagination|bool $value the pagination to be used by this data provider.
     *                                     可以是下面描述中的其中一个：
     *                                     This can be one of the following:
     *
     * - 一个配置数据，用于创建分页对象。“类”元素默认是'yii\data\Pagination'
     * - a configuration array for creating the pagination object. The "class" element defaults
     *   to 'yii\data\Pagination'
     * - 一个实现[[Pagination]]或者它的子类。
     * - an instance of [[Pagination]] or its subclass
     * - 布尔值“false”，禁用分页
     * - false, if pagination needs to be disabled.
     *
     * @throws InvalidParamException
     */
    public function setPagination($value)
    {
        if (is_array($value)) {
            $config = ['class' => Pagination::className()];
            if ($this->id !== null) {
                $config['pageParam'] = $this->id . '-page';
                $config['pageSizeParam'] = $this->id . '-per-page';
            }
            $this->_pagination = Yii::createObject(array_merge($config, $value));
        } elseif ($value instanceof Pagination || $value === false) {
            $this->_pagination = $value;
        } else {
            throw new InvalidParamException('Only Pagination instance, configuration array or false is allowed.');
        }
    }

    /**
     * 返回数据提供值的排序对象。
     * Returns the sorting object used by this data provider.
     * 返回排序对象，如果是false，认为排序不可用。
     * @return Sort|bool the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort()
    {
        if ($this->_sort === null) {
            $this->setSort([]);
        }

        return $this->_sort;
    }

    /**
     * 为数据提供者设置排序规则。
     * Sets the sort definition for this data provider.
     *  $value 被用于数据提供者的排序规则，
     * @param array|Sort|bool $value the sort definition to be used by this data provider.
     *                               可以是下面中的一个：
     *                               This can be one of the following:
     *
     * - 一个配置数组为创建排序对象。“类”元素默认是'yii\data\Sort'
     * - a configuration array for creating the sort definition object. The "class" element defaults
     *   to 'yii\data\Sort'
     * - 一个实现【Sort】或者它的子类
     * - an instance of [[Sort]] or its subclass
     * - false,排序是否需要被禁用。
     * - false, if sorting needs to be disabled.
     *
     * @throws InvalidParamException
     */
    public function setSort($value)
    {
        if (is_array($value)) {
            $config = ['class' => Sort::className()];
            if ($this->id !== null) {
                $config['sortParam'] = $this->id . '-sort';
            }
            $this->_sort = Yii::createObject(array_merge($config, $value));
        } elseif ($value instanceof Sort || $value === false) {
            $this->_sort = $value;
        } else {
            throw new InvalidParamException('Only Sort instance, configuration array or false is allowed.');
        }
    }

    /**
     * 刷新数据的提供者
     * Refreshes the data provider.
     * 调用此方法，将会调用[[getModels()]], [[getKeys()]] or [[getTotalCount()]]这三个方法。
     * After calling this method, if [[getModels()]], [[getKeys()]] or [[getTotalCount()]] is called again,
     * 它们将重新执行查询并返回可用的最新数据。
     * they will re-execute the query and return the latest data available.
     */
    public function refresh()
    {
        $this->_totalCount = null;
        $this->_models = null;
        $this->_keys = null;
    }
}
