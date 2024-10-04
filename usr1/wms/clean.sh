:
cd /usr1/wms
for dir in Inbound Outbound dbg;do
echo "Cleaning $dir"
cd $dir
find . -name "w*" -mtime +3 -exec rm {} \;
#find . -name "w*" -mtime +10 -exec rm {} \;
cd ..
done

