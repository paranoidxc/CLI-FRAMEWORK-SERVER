# CLI-FRAMEWORK-SERVER

Usage:
- php >= 7.1
- php configure  --enable-pcntl --enable-sockets -with-openssl=/usr/bin/openssl
- 启动server php server_for_github.php
- 浏览器打开 127.0.0.1:9000/ 或者 127.0.0.1:9000/[a|b|c].php

Todo List:
- [ ] 守护进程模式运行
- [ ] 监控子进程
- [ ] 多进程模式
- [ ] IO复用 Select 模式
- [ ] IO复用 EPOLL 模式
- [ ] 支持 HTTP 协议
- [ ] 代码重构成面向对象模式
- [ ] WEB 框架
