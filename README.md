# iitc
ingress 相關應用。例如：藍八警戒系統、成就系統

# Architecture
## 命名規則
* blue8 藍八警戒功能
* killer 成就獵殺功能
* notification 通知功能
* search 查詢功能, 透過下指令來取得資料
		API
		如何取得 bot 的指令，回傳資料
* test 測試中腳本

# maintain
* check mysql max package size
mysql -u root -p
show variables like 'max_allowed_packet';
* let mysql reload config value
set global max_allowed_packet=700000;
sudo service mysql restart
mysql -u root -p
show variables like 'max_allowed_packet';

# add as routine
https://blog.longwin.com.tw/2018/02/linux-systemd-auto-start-daemon-service-2018/
## script
$ sudo vi /etc/systemd/system/YOUR_SERVICE_NAME.service
[Unit]
Description=example daemon

[Service]
Type=simple
WorkingDirectory=/PHP/SCRIPT/DIRECTORY/
ExecStart=/usr/bin/php /PHP/SCRIPT/PATH
Restart=always

[Install]
WantedBy=multi-user.target

## run (auto)
$ sudo systemctl daemon-reload
$ sudo systemctl restart YOUR_SERVICE_NAME

## stop it
$ sudo systemctl stop YOUR_SERVICE_NAME

# install netdata
https://github.com/netdata/netdata#quick-start
	docker run -d --name=netdata \
		-p 19999:19999 \
		-v /etc/passwd:/host/etc/passwd:ro \
		-v /etc/group:/host/etc/group:ro \
		-v /proc:/host/proc:ro \
		-v /sys:/host/sys:ro \
		-v /var/run/docker.sock:/var/run/docker.sock:ro \
		--cap-add SYS_PTRACE \
		--security-opt apparmor=unconfined \
		netdata/netdata

* check it
http://knem.chickenkiller.com:19999


# docs
* (X) [API](./docs/apis.md)
* [function list](./docs/function_list.xlsx)
* [changeLogs](./docs/changeLogs.md)