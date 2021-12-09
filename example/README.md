#使用例子
#### 当前文件夹下所有文件都是提供复制到项目中使用

##config文件夹
config常用配置，复制到项目中使用

##http转发grpc网关例示
2021-7-26

把以下文件复制到项目中
\Controller\AbstratorController
\Gateway\*
\Middleware\GrpcRequestMiddleware
\grpc.proto
在route.php中追加路由

原理：

1.\Gateway\文件接收http请求，转发grpc请求。

2.grpc请求通过中间件获取到参数，将参数写到上下文中，具体逻辑获取参数($this->request->all())时，
方法从上下文中取出参数，并过滤返回。接下来运行逻辑和http一样。
返回时，AbstratorController::success通过判断上下文中是否标记grpc请求。返回Reply对象。