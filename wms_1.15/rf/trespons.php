<?php

$css=<<<HTML
/* 
Max width before this PARTICULAR table gets nasty
This query will take effect for any screen smaller than 760px
and also iPads specifically.
*/
@media 
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {

	/* Force table to not be like tables anymore */
	table, thead, tbody, th, td, tr { 
		display: block; 
	}
	
	/* Hide table headers (but not display: none;, for accessibility) */
	thead tr { 
		position: absolute;
		top: -9999px;
		left: -9999px;
	}
	
	tr { border: 1px solid #ccc; }
	
	td { 
		/* Behave  like a "row" */
		border: none;
		border-bottom: 1px solid #eee; 
		position: relative;
		padding-left: 50%; 
	}
	
	td:before { 
		/* Now like a table header */
		position: absolute;
		/* Top/left values mimic padding */
		top: 6px;
		left: 6px;
		width: 45%; 
		padding-right: 10px; 
		white-space: nowrap;
	}
	
	/*
	Label the data
	*/
        td:before { content: attr(data-label); }
}
HTML;

$htm=<<<HTML
<table role="table">
  <thead role="rowgroup">
    <tr role="row">
      <th role="columnheader">First Name</th>
      <th role="columnheader">Last Name</th>
      <th role="columnheader">Job Title</th>
      <th role="columnheader">Favorite Color</th>
      <th role="columnheader">Wars or Trek?</th>
      <th role="columnheader">Secret Alias</th>
      <th role="columnheader">Date of Birth</th>
      <th role="columnheader">Dream Vacation City</th>
      <th role="columnheader">GPA</th>
      <th role="columnheader">Arbitrary Data</th>
    </tr>
  </thead>
  <tbody role="rowgroup">
    <tr role="row">
      <td data-label="First Name" role="cell">James</td>
      <td data-label="Last Name" role="cell">Matman</td>
      <td data-label="Job Title" role="cell">Chief Sandwich Eater</td>
      <td data-label="Favorite Color" role="cell">Lettuce Green</td>
      <td data-label="Wars or Trek?" role="cell">Trek</td>
      <td data-label="Secret Alias" role="cell">Digby Green</td>
      <td data-label="Date of Birth" role="cell">January 13, 1979</td>
      <td data-label="Dream Vacation City" role="cell">Gotham City</td>
      <td data-label="GPA" role="cell">3.1</td>
      <td data-label="Arbitrary Data" role="cell">RBX-12</td>
    </tr>
    <tr role="row">
      <td data-label="First Name" role="cell">The</td>
      <td data-label="Last Name" role="cell">Tick</td>
      <td data-label="Job Title" role="cell">Crimefighter Sorta</td>
      <td data-label="Favorite Color" role="cell">Blue</td>
      <td data-label="Wars or Trek?" role="cell">Wars</td>
      <td data-label="Secret Alias" role="cell">John Smith</td>
      <td data-label="Date of Birth" role="cell">July 19, 1968</td>
      <td data-label="Dream Vacation City" role="cell">Athens</td>
      <td data-label="GPA" role="cell">N/A</td>
      <td data-label="Arbitrary Data" role="cell">Edlund, Ben (July 1996).</td>
    </tr>
  </tbody>
</table>
HTML;

$h=<<<HTML
<!DOCTYPE html>
<html lang="en" >

<head>

  <meta charset="UTF-8">
 <title>CodePen - Responsive Table Demo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  
  
<style>
{$css}
</style>
</head>

<body translate="no" >
{$htm}
</body>
</html>

HTML;
echo $h;
