PATH="/usr/lib/git-core:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export PATH

homedir="/var/www/wms/daemons"
cd $homedir

ftpIn=`php getVars.php 0`
wmsDir=`php getVars.php 1`
Top=`php getVars.php 2`
sleeptime=`php getVars.php 3`

#echo "wmsDir=$wmsDir\nTop=$Top\nsleep=$sleeptime\n"

#Change owner, group and permissions so it can be moved by www-data
# and check if there is files to process
aa=`ls $ftpIn/*.txt 2>/dev/null`
if [ "$aa" = "" ]; then
    echo 'No Files to Process'
    exit 0
else
 /usr1/client/t2 <<-eof
    chown www-data $ftpIn/*txt
    chgrp www-data $ftpIn/*txt
    chmod 777 $ftpIn/*txt
eof

fi

onetime="N"
if [ "$1" != "" ]
then
 onetime="Y"
fi

tmpDir="/tmp"
logfile="$tmpDir/incoming.log"
lockfile="$tmpDir/inLock.lck"
stopfile="$tmpDir/inStop"
xlogfile="$tmpDir/xincoming.log"
rm -f $xlogfile
if [ -s "$logfile" ]
then
 mv $logfile $xlogfile
fi
echo " WMS Monitoring Incoming Files\c"  > $logfile
date >> $logfile
phpserver="localhost"
phpdir="$Top/Inbound"
# this script resides in /var/www/wms/daemons
# php script to run resides in /var/www/wms/Inbound


#check Lock file
if [ ! -f $lockfile ]
then
  rm -f $stopfile
  echo " WMS Monitoring Incoming Files\c"
echo $$ > $lockfile
ok=1
while [ $ok -eq 1 ]
do
lynx -dump http://$phpserver/$phpdir/in.php >>$logfile 2>&1
 if [ -f $stopfile ]
 then
  echo "exiting..."
  rm -f $lockfile $stopfile
  ok=0
  exit
 fi
 if [ "$onetime" = "Y" ]
 then
  echo "exiting..."
  rm -f $lockfile $stopfile
  exit
 fi
 sleep $sleeptime
done
else
 a=`cat $lockfile`
 echo "incoming is running on pid $a"
 exit;
fi
