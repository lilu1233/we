![微擎](http://cdn.w7.cc/web/resource/images/wechat/logo/logo.png)

### 微擎开源微信公众号管理系统

感谢您选择微擎系统。

微擎是一款免费开源的微信公众号管理系统，基于目前最流行的WEB2.0架构（php+mysql），支持在线升级和安装模块及模板，拥有良好的开发框架、成熟稳定的技术解决方案、活跃的第三方开发者及开发团队，依托微擎开放的生态系统，提供丰富的扩展功能。

### 运行环境
IIS/Apache/Nginx、PHP >=5.3、MySQL>=5.0
运行微擎系统必须保证环境版本满足上述要求，具体环境检测可以运行 _install.php_ 文件进行检测。

### 目录结构
请确保您将微擎程序文件放置在您的网站目录中，微擎项目目录结构如下：
```
    addons             微擎模块
    api                对接外部系统接口
    app                微站 （Mobile / App）
    attachment         附件目录
    framework          微擎框架
    payment            支付调用目录
    tester             测试用例
    upgrade            升级脚本
    web                后台管理
    api.php            微信api接口
    index.php          系统入口
    install.php        安装文件
    password.php       密码重置
```

### 在线安装
请到这里下载安装文件：http://s.w7.cc/static/install

### 离线安装
代码clone完成后，浏览器内输入：您的域名/install.php 来执行安装

### 更新
您可以通过 _Master_ 分支得到微擎目前版本最新的代码，但是此代码未通过小规模测试及上线测试，所以在您正式的环境中请还是通过云服务进行一键升级。
除 _Master_ 分支外，其它分支皆为开发版本，仅供大家了解微擎最新开发功能。
我们会将每次升级中产生的数据库变更SQL语句存放在项目目录的 _upgrade_ 目录中，供开发者进行离线升级。将来我们也会引入一些在离线状态下自动化升级的方案。

##### 执行更新（微擎内部开发人员使用）
>暂不支持文件更新 此更新只包含数据库更新,文件需自己覆盖  
执行如下命令  
 
`php console.php upgrade `

会提示更新  输入Y 更新

##### 创建本地更新文件（微擎内部开发人员使用）
>创建本地更新文件只有微擎内部开发人员使用

`php console.php make:upgrade name={name}`

示例  微擎内部开发人员使用

`php console.php make:upgrade name=update_uniaccount`
### 后续
您可以通过查看我们的文件，来对系统进一步的了解和开发、开发模块。文档地址为 http://s.w7.cc/index.php?c=wiki&do=view&id=1 我们会不断的更新文档内容。
如果在文档中有未尽事宜您可以通过微擎开发者群来与我们取得联系，群号为：①310579684 ②77730481。当您模块开发完毕可以通过我们的应用商城进行发布，地址为：http://s.w7.cc 。

再次感谢您对我们的支持，欢迎您对我们的程序提出意见，我们期待您的Pull Request。
