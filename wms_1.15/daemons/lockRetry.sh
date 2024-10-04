PATH="/usr/lib/git-core:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export PATH

homedir="/var/www/wms/daemons"
cd $homedir

wmsDir=`php getVars.php 1`
Top=`php getVars.php 2`
sleeptime=`php getVars.php 5` # lock retry sleep time

onetime="N"
if [ "$1" != "" ]
then
 onetime="Y"
fi

tmpDir="/tmp"
logfile="$tmpDir/retry.log"
lockfile="$tmpDir/retry.lck"
stopfile="$tmpDir/LRStop"
xlogfile="$tmpDir/xretry.log"
rm -f $xlogfile
if [ -s "$logfile" ]
then
 mv $logfile $xlogfile
fi
echo " WMS Monitoring for Locked Record on Communicatons to ERP\c"  > $logfile
date >> $logfile
phpserver="localhost"
phpdir="$Top/daemons"
# this script and php script resides in /var/www/wms/daemons

    #check Lock file
    if [ ! -f $lockfile ]
    then
      rm -f $stopfile
      echo " WMS Monitoring for Locked Record on Communicatons to ERP\c" 
    echo $$ > $lockfile
    ok=`echo 'select count(*) from WMSCOMMERR where statusCode = 423;' | MYSQL -N`
    while [ $ok -gt 0 ]
    do
    lynx -dump http://$phpserver/$phpdir/retryLock.php >>$logfile 2>&1
     if [ -f $stopfile ]
     then
      echo "exiting..."
      rm -f $lockfile $stopfile
      ok=`echo 'select count(*) from WMSCOMMERR where statusCode = 423;' | MYSQL -N`
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
 echo "retryLock is running on pid $a"
 exit;
fi
