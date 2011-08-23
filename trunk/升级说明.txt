1. 从SpeedPHP v3.x升级到v3.2，只需要覆盖SpeedPHP框架目录文件即可。

2. 从SpeedPHP v2.x升级到v3.2，需要对入口文件进行一个修改操作：

请在入口文件（通常是index.php）中：require(SP_PATH."/SpeedPHP.php");这行之下，加入spRun();函数的调用。

也就是说，一个通常的入口文件将是：

[code]
<?php
define("APP_PATH",dirname(__FILE__));
define("SP_PATH", APP_PATH."/SpeedPHP");
$spConfig = array(

);
require(SP_PATH."/SpeedPHP.php");
spRun(); // 新加入的spRun函数调用！

[/code]

3. 对于已有的静态HTML文件，请删除缓存文件以及原有静态文件，重新生成。








