PATH="/usr/lib/git-core:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
export PATH

homedir="wms/daemons"
cd /var/www/$homedir

wmsDir=`php getVars.php 1`
Top=`php getVars.php 2`
sleeptime=`php getVars.php 4`

#echo "wmsDir=$wmsDir\nTop=$Top\nsleep=$sleeptime\n"

tmpDir="/tmp"
logfile="$tmpDir/allocate.log"
lockfile="$tmpDir/allocate.lck"
stopfile="$tmpDir/AllStop"
xlogfile="$tmpDir/xallocate.log"
rm -f $xlogfile
if [ -s "$logfile" ]
then
 mv $logfile $xlogfile
fi
echo " WMS Allocating Incoming Orders\c"  > $logfile
date >> $logfile
phpserver="localhost"
phpdir="$Top/Inbound"
# this script resides in /var/www/wms/daemons
# php script to run resides in /var/www/wms/Inbound


#check Lock file
if [ ! -f $lockfile ]
then
  rm -f $stopfile
  echo " WMS Allocating new Orders\c"
echo $$ > $lockfile
ok=1
while [ $ok -eq 1 ]
do
#lynx -dump http://$phpserver/$homedir/allocate.php >>$logfile 2>&1
lynx -dump http://$phpserver/$homedir/allocate.php?Top=$Top
rm /tmp/all*
exit
 if [ -f $stopfile ]
 then
  echo "exiting..."
  rm -f $lockfile $stopfile
  ok=0
  exit
 fi
 #sleep $sleeptime
 ok=1
done
else
 a=`cat $lockfile`
 echo "Allocation is running on pid $a"
 exit;
fi

