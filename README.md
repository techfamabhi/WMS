# RealWMS

#wms Directory
 .	  Main directory (access in Browser http://{ip address or domain}/wms/)
 IC	  Inventory Control Programs
 Inbound  Inbound (Receiving Desktop Programs)
 Outbound Outboud (Sales Orders, Transfers, Debits Desktop programs)
 RPTS	  Reports
 SYS	  System Maintenance Desktop programs
 Themes	  Themes to control Desktop appearance
 WHSE	  Warehouse Maintenance Programs
 assets	  
	* css		Css Files
	* docs		User Documentation
	* help		User Help Files
	* images	Images used by downloaded packages
	* js		Downloaded js packages used
	* pdf		Generate PDF file classes
	* plugins	Supporting plugins required for js and css
	* sounds	Sounds used in the site
 daemons  Backgound programs
 images	  Images used in the site
 include  Classes used in the site
 loadData Data Utilities to bulk load part and inventory data
 labels	  Label programs
 rf	  Programs that run on the handhelds
 servers  REST servers used by the site

#jq Directory
 Javascript packages that are used throughout the site that should eventually be moved to assets. Mainly used to try different versions, since some versions don't work on all tested devices so far.

 Once all the versions are worked out, the correct versions will be moved to assets.

 These include;
  Links;
  href="/jq/bootstrap.min.css"                           rel="stylesheet">
  href="/jq/jquery-ui.css.1"             type="text/css" rel="stylesheet">
  href="/jq/tab\_style.css"               type="text/css" rel="stylesheet">

  Javascripts;
  src="/jq/axios.min.js"               type="text/javascript">
  src="/jq/vue_2.6.14_min.js"          type="text/javascript">

  src="/jq/jquery-1.12.4.js"           type="text/javascript">
  src="/jq/jquery-1.9.1.min.js"        type="text/javascript">
  src="{$javascripts}/jquery-3.3.1.js" type="text/javascript">

  src="/jq/shortcut.js"                type="text/javascript">
  src="/jq/jquery-ui.js"               type="text/javascript">

# eScan 1.15
# eScan 1.15
# eScan 1.15
