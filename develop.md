 发布信息
 =======

 版本1.0.0（2017/3/24-）
 -------

 ### 1、功能
 
 migration.php中的up()和down()方法与safeUp()和safeDown()方法最大的不同是：
 up()和down()可能一部分执行成功，一部分失败，而safeUp()和safeDown()执行之前会先执行up()或者down()来开启事务，
 根据safeUp()和safeDown()执行的结果来确定是提交事务还是回滚事务 。--待测试
 
 ### 2、通过命令行创建gii
 具体的代码逻辑可以根据gii/参数 
 的参数值来查看vendor/yiisoft/yii2-gii/generators/参数值/Generator.php文件
 
 1. 可以通过页面访问创建默认的控制器，模型，安装yii2之后通过访问
 /index.php?r=gii
 
 2. 可以通过命令行生成对应的代码
 
 首先根据表生成模型：--ns不设置，默认存放在models文件夹中
 ./yii gii/model [--ns=命名空间] --tableName=表名称 --modelClass=模型名称
 例如：
 ./yii gii/model --tableName=user_info --modelClass=UserInfo
 
 生成对应的curd:
  ./yii gii/crud --controllerClass=控制器名称（含命名空间） --modelClass=模型名（含命名空间）
 --searchModelClass=模型名+Search
 例如：
  ./yii gii/crud --controllerClass=app\controllers\UserInfoController --modelClass=app\models\UserInfo --searchModelClass=app\models\UserInfoSearch
 