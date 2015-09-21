SpeedPHP是一款全功能的国产PHP框架应用系统，速度飞快，上手容易，是最适合初学者的PHP框架。SpeedPHP以“快速开发、快速学习、快速执行”为理念，带你轻松进入PHP高手的行列。

新版SpeedPHP测试版本已经在Git@OSC发布，请移步：https://git.oschina.net/SpeedPHP/speed

**主页**：http://www.speedphp.com/



SpeedPHP 3.1.89是修正了3.1.66多项存在问题的稳定版本，增强对SAE（新浪云计算平台）、更新Smarty 3。



**下载地址**：http://www.speedphp.com/download（有UTF8、GBK和SAE专用版本下载）



**升级方法：**



speedphp 3.0升级到speedphp 3.1.89，直接覆盖框架文件即可。

speedphp 2.x升级到speedphp 3.1.89，请参考压缩包内UPDATE.txt文件说明

**新版本3.1.89：**



升级Smarty 3，更稳定

修正了URLREWRITE伪静态的存在问题

修正全部已知的bug



**ChangLog?：**



无临时目录情况下将自动新建目录而不是提示错误

在sae环境未开启mysql时将提示

去除文件缓存多级目录的处理，因其会带来管理上的开销。

修正spAccessCache驱动在3.1.66中存在的两个问题，并修改部分注释

修改升级说明文档

smarty类库更新到3.0.8版

修正部分已发现的bug

修正了bae平台的一些禁用函数

增加本地调试与SAE平台自动切换的附加程序

增加百度开发平台BAE分支

对ORACLE驱动构造时增加了时间格式的设置命令

修正import载入扩展的顺序问题

测试UrlRewrite?不区分大小写是否合理

调整了判断PHP版本的代码位置

gbk版本mysql驱动重新加入set names语句

添加独立数据库操作类库分支

修正了spUrlRewrite会在后缀为空的时候加入问号结束的bug

修改mysql，mysqli，mssql三个驱动程序的getArray函数在查询结果为空时返回空数组array()

增加配置cache_multidir，让spAccess的缓存文件分目录生成，避免缓存文件过分集中在同一目录。

修正spUrlRewrite在r170版本中生成地址的判断。

修改spVerifier对未知规则的提示。

更新PDO驱动，修复了UTF8的支持。

spUrlRewrite在URL为控制器的时候，接收和生成地址都进行了调整。

spUrlRewrite调整为在URL为控制器名称时，将可忽略默认action名称。

修复了模板引擎检查临时目录无效的问题。

改正一个注释错误

**SpeedPHP 3.0正式版**



SpeedPHP 3 的使用基本和SpeedPHP第二版相同，所以SpeedPHP第二版手册也能在SpeedPHP 3 上面使用。



新特性：



1. 支持多种数据库类型：MySQLi、Sqlite、Oracle、MsSQL、PDO等。



2. 加入新浪云计算SAE分支版本、加入GBK分支版本（新浪云计算平台专用）。



3. 支持多种模板引擎：Smarty、Template Lite、speedy等。



4. 支持多种缓存机制：Memcache、Xcache、APC、eAccelerator等。



5. 改进数据库及模板引擎驱动结构，改进控制器与视图类的逻辑结构，更合理并进一步节省资源。



6. 多项原有功能强化、改进spHTML生成静态功能、增强静态化URL模式。



7. 增加spRun，spDB，replace、affectedRows、runSql、escape、



8. 修正SpeedPHP第二版发布以来的许多bug，稳定性有了极大提升。



9. 保持一致的应用程序配置，SP2成员仅在入口文件中加入spRun即可。



10. 保持一贯的简便、易学、轻巧。
