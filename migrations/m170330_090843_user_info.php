<?php

use yii\db\Migration;

class m170330_090843_user_info extends Migration
{
    public static $tableName = 'user_info';

    public function safeUp()
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB COMMENT "基础店铺表"';
        }
        $this->createTable(self::$tableName, [
            'id'       => $this->primaryKey(),
            'name'     => $this->string()->notNull()->comment('用户名称'),
            'type'     => $this->integer()->notNull()->defaultValue(2)->comment('职业：1、学生；2：工人；3：教师；4：农民'),
            'image'    => $this->string()->comment('照片'),
            'phone'    => $this->string(11)->notNull()->comment('手机号码'),
            'birthday' => $this->date()->comment('出生日期'),
            'describe' => $this->text()->comment('用户描述'),
            'cost'     => $this->money()->comment('花费'),
        ], $tableOptions);

        $this->createIndex('name', self::$tableName, 'name');
    }

    public function safeDown()
    {
        $this->dropTable(self::$tableName);
    }
}
