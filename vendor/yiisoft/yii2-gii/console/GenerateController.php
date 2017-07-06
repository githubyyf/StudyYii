<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\gii\console;

use Yii;
use yii\base\InlineAction;
use yii\console\Controller;

/**
 * 这是gii的命令行版本--代码生成器
 * This is the command line version of Gii - a code generator.
 *
 * 你可以用这个命令生成模型，控制器等，例如，生成数据库中存在的表的的记录模型，执行如下操作：
 * ./yii gii/model --tableName=表的名称 --modelClass=生成的模型名称
 *
 * You can use this command to generate models, controllers, etc. For example,
 * to generate an ActiveRecord model based on a DB table, you can run:
 *
 * ```
 * $ ./yii gii/model --tableName=city --modelClass=City
 * ```
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class GenerateController extends Controller
{
    /**
     * @var \yii\gii\Module
     */
    public $module;
    /**
     * 在非交换模式下是否覆盖所有的现有代码。默认是否，意味着现有的代码文件都不会被覆盖，
     * 这个操作只被用在`--interactive=0`
     * @var boolean whether to overwrite all existing code files when in non-interactive mode.
     * Defaults to false, meaning none of the existing code files will be overwritten.
     * This option is used only when `--interactive=0`.
     */
    public $overwrite = false;
    /**
     * 数组，-可用代码生成器的列表
     * @var array a list of the available code generators
     */
    public $generators = [];

    /**
     * 数组，-代码生成器操作的值
     * @var array generator option values
     */
    private $_options = [];


    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        $this->_options[$name] = $value;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        foreach ($this->generators as $id => $config) {
            $this->generators[$id] = Yii::createObject($config);
        }
    }

    /**
     * @inheritdoc
     */
    public function createAction($id)
    {
        /** @var $action GenerateAction */
        $action = parent::createAction($id);
        foreach ($this->_options as $name => $value) {
            $action->generator->$name = $value;
        }
        return $action;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = [];
        foreach ($this->generators as $name => $generator) {
            $actions[$name] = [
                'class' => 'yii\gii\console\GenerateAction',
                'generator' => $generator,
            ];
        }
        return $actions;
    }

    public function actionIndex()
    {
        $this->run('/help', ['gii']);
    }

    /**
     * @inheritdoc
     */
    public function getUniqueID()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function options($id)
    {
        $options = parent::options($id);
        $options[] = 'overwrite';

        if (!isset($this->generators[$id])) {
            return $options;
        }

        $attributes = $this->generators[$id]->attributes;
        unset($attributes['templates']);
        return array_merge(
            $options,
            array_keys($attributes)
        );
    }

    /**
     * @inheritdoc
     */
    public function getActionHelpSummary($action)
    {
        if ($action instanceof InlineAction) {
            return parent::getActionHelpSummary($action);
        } else {
            /** @var $action GenerateAction */
            return $action->generator->getName();
        }
    }

    /**
     * @inheritdoc
     */
    public function getActionHelp($action)
    {
        if ($action instanceof InlineAction) {
            return parent::getActionHelp($action);
        } else {
            /** @var $action GenerateAction */
            $description = $action->generator->getDescription();
            return wordwrap(preg_replace('/\s+/', ' ', $description));
        }
    }

    /**
     * @inheritdoc
     */
    public function getActionArgsHelp($action)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getActionOptionsHelp($action)
    {
        if ($action instanceof InlineAction) {
            return parent::getActionOptionsHelp($action);
        }
        /** @var $action GenerateAction */
        $attributes = $action->generator->attributes;
        unset($attributes['templates']);
        $hints = $action->generator->hints();

        $options = parent::getActionOptionsHelp($action);
        foreach ($attributes as $name => $value) {
            $type = gettype($value);
            $options[$name] = [
                'type' => $type === 'NULL' ? 'string' : $type,
                'required' => $value === null && $action->generator->isAttributeRequired($name),
                'default' => $value,
                'comment' => isset($hints[$name]) ? $this->formatHint($hints[$name]) : '',
            ];
        }

        return $options;
    }

    protected function formatHint($hint)
    {
        $hint = preg_replace('%<code>(.*?)</code>%', '\1', $hint);
        $hint = preg_replace('/\s+/', ' ', $hint);
        return wordwrap($hint);
    }
}
