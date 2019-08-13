#!/bin/bash
cron
sleep 1
crontab /data/itom_cron
OLDIFS=$IFS
IFS=$'\n'
arr=('instancemqworker copy' 'instancemqworker include' 'command createcan' 'command updatecans' 'command cancmd' 'command cmd' 'command upimages' 'command pullimages' 'command task' 'command stopcans')

while true; do
    for var in ${arr[@]}
    do
        IFS=$'\n'
        PID=`ps aux|grep ${var} |grep -v grep|sed -n 1p|awk '{print $2}'`
        IFS=$OLDIFS
         # 检查进程是否存在
        if [ "-$PID" == "-" ];then
           echo "The process ${var} does not exist."
           echo "Now start the process............"
           logName=`echo $var|cut -d ' ' -f 2`
           `php /app/cli.php $var >> /app/script/log/${logName}"_sh.log" 2>&1 &`
        fi
    done

    echo 1
    sleep 60
    OLDIFS=$IFS
    IFS=$'\n'
done