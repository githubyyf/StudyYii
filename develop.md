 发布信息
 =======

 版本1.0.0（2017/3/24-）
 -------

 ### 1、功能
 
 migration.php中的up()和down()方法与safeUp()和safeDown()方法最大的不同是：
 up()和down()可能一部分执行成功，一部分失败，而safeUp()和safeDown()执行之前会先执行up()或者down()来开启事务，
 根据safeUp()和safeDown()执行的结果来确定是提交事务还是回滚事务 。--待测试