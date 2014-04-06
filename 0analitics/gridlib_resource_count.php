<?php
// set up DB
$conn = mysql_connect("localhost", "moodle24", "admin5");
mysql_select_db("moodle24");
// set your db encoding -- for ascent chars (if required)
mysql_query("SET NAMES 'utf8'");
// include and create object
include("gridlib/inc/jqgrid_dist.php");
$g = new jqgrid();

$col = array();
$col["title"] = "Кафедра"; // caption of column
$col["name"] = "mccname"; 
#$col["width"] = "10";
# $col["hidden"] = true; // hide column by default
$cols[] = $col;	

$col = array();
$col["title"] = "Код"; // caption of column
$col["name"] = "cid"; 
$col["width"] = "20";
# $col["hidden"] = true; // hide column by default
$cols[] = $col;	

$col = array();
$col["title"] = "Назва дисципліни"; // caption of column
$col["name"] = "cfullname"; 
$col["resizable"] = true;
#$col["width"] = "100";
# $col["hidden"] = true; // hide column by default
$cols[] = $col;	

$col = array();
$col["title"] = "Лінк"; // caption of column
$col["name"] = "idlink"; 
$col["width"] = "30";
$col["search"] = false;
$col["align"] = "center";
# $col["hidden"] = true; // hide column by default
$cols[] = $col;	

$col = array();
$col["title"] = "Тем(занять)";
$col["name"] = "sections";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col = array();
$col["title"] = "Тестів";
$col["name"] = "quizes";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col = array();
$col["title"] = "Файлів";
$col["name"] = "resources";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col = array();
$col["title"] = "Файлів PDF";
$col["name"] = "resources_pdf";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col = array();
$col["title"] = "Папок";
$col["name"] = "folders";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;
$col = array();

$col["title"] = "Файлів в папках";
$col["name"] = "files_in_folders";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;
$col = array();

$col["title"] = "PDF в папках";
$col["name"] = "pdf_in_folders";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col["title"] = "PPT в папках";
$col["name"] = "ppt_in_folders";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;

$col = array();
$col["title"] = "Посилань";
$col["name"] = "urls";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;
$col = array();

$col = array();
$col["title"] = "Матрикулів";
$col["name"] = "checklists";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;
$col = array();

$col = array();
$col["title"] = "Розкладів";
$col["name"] = "schedulers";
$col["width"] = "50";
$col["search"] = false;
$col["align"] = "center";
$col["summaryType"] = "sum"; // available grouping fx: sum, count, min, max
$col["summaryTpl"] = '<b>Всього: {0}</b>'; // display html for summary row - work when "groupSummary" is set true. search below
$cols[] = $col;
$col = array();

// set few params
// caption of grid
$grid["caption"] = "Кількість ресурсів по курсах. Сумарно та по типах - тести, посилання, файли, папки, контрольні списки, розклади";

//Show corner (lower-right) resizable option on grid
$grid["resizable"] = false;
// expand grid to screen width
$grid["autowidth"] = true; 
$grid["height"] = "100%";
$grid["hidegrid"] = false;

//Enable or Disable total records text on grid
$grid["viewrecords"] = true;
// you can also set 'All' for all records
//$grid["rowList"] = array();
$grid["rowList"] = array(20,50,100,'All');
$grid["rowNum"] = 100;
//$grid["scroll"] = true; 
//groupings
$grid["grouping"] = true;
$grid["groupingView"] = array();

// specify column name to group listing
$grid["groupingView"]["groupField"] = array("mccname"); 

// either show grouped column in list or not (default: true)
$grid["groupingView"]["groupColumnShow"] = array(true); 

// {0} is grouped value, {1} is count in group
$grid["groupingView"]["groupText"] = array("<b>{0} - {1} дисциплін(а)</b>"); 

// show group in asc or desc order
$grid["groupingView"]["groupOrder"] = array("asc"); 

// show sorted data within group
$grid["groupingView"]["groupDataSorted"] = array(true); 

// work with summaryType, summaryTpl, see column: $col["name"] = "total";
$grid["groupingView"]["groupSummary"] = array(true); 

// Turn true to show group collapse (default: false) 
$grid["groupingView"]["groupCollapse"] = false; 

// show summary row even if group collapsed (hide) 
$grid["groupingView"]["showSummaryOnHide"] = false; 



// export XLS file
// export to excel parameters
$grid["export"] = array("format"=>"xlsx", "filename"=>"my-file", "sheetname"=>"test");
$grid["export"] = array("format"=>"pdf", "filename"=>"my-file", "sheetname"=>"test");

$g->set_options($grid);

$g->set_actions(array(	
						"add"=>false, // allow/disallow add
						"edit"=>false, // allow/disallow edit
						"delete"=>false, // allow/disallow delete
						"rowactions"=>true, // show/hide row wise edit/del/save option
						"export"=>true, // show/hide export to excel option
						"autofilter" => true, // show/hide autofilter for search
						"search" => "advance" // show single/multi field search condition (e.g. simple or advance)
					) 
				);
// set database table for CRUD operations
$g->select_command = 
"SELECT mcc.name AS mccname, c.id AS cid, c.fullname AS cfullname, concat('<a target=\"_new\" href=\"http://moodle.tdmu.edu.ua/course/view.php?id=',c.id,' \">Link</a>') AS idlink,  
COUNT(cs.id) AS sections, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 12 AND cm.visible=1) AS quizes, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 13 AND cm.visible=1) AS resources, 
(SELECT count(*) 
 FROM mdl_files f 
 JOIN mdl_context ctx ON f.contextid = ctx.id 
 JOIN mdl_course_modules cm ON ctx.instanceid=cm.id
 WHERE cm.module=13 and f.filename like '%.pdf%' and cm.course = c.id) AS resources_pdf, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 22 AND cm.visible=1) AS folders, 
(SELECT count(*) 
 FROM mdl_files f 
 JOIN mdl_context ctx ON f.contextid = ctx.id 
 JOIN mdl_course_modules cm ON ctx.instanceid=cm.id
 WHERE cm.module=22 and f.filesize>0 and cm.course = c.id) AS files_in_folders, 
(SELECT count(*) 
 FROM mdl_files f 
 JOIN mdl_context ctx ON f.contextid = ctx.id 
 JOIN mdl_course_modules cm ON ctx.instanceid=cm.id
 WHERE cm.module=22 and f.filename like '%.pdf%' and cm.course = c.id) AS pdf_in_folders, 
(SELECT count(*) 
 FROM mdl_files f 
 JOIN mdl_context ctx ON f.contextid = ctx.id 
 JOIN mdl_course_modules cm ON ctx.instanceid=cm.id
 WHERE cm.module=22 and f.filename like '%.ppt%' and cm.course = c.id) AS ppt_in_folders, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 26 AND cm.visible=1) AS urls, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 30 AND cm.visible=1) AS checklists, 
(SELECT COUNT(*) FROM mdl_course_modules AS cm WHERE cm.course = c.id AND cm.module= 31 AND cm.visible=1) AS schedulers 
FROM mdl_course_sections AS cs  
JOIN mdl_course as c ON c.id=cs.course 
LEFT JOIN mdl_course_categories mcc ON c.category=mcc.id
WHERE cs.visible=1 and length(cs.sequence)>0 and c.category<>0 and c.category<>45
GROUP BY c.id";

// pass the cooked columns to grid
$g->set_columns($cols);

// render grid and get html/js output
$out = $g->render("list1");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="uk" lang="uk">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Аналітичний відділ. СДО Moodle. Кількість файлів-ресурсів. Статистичні дані.</title> 
    <!-- these css and js files are required by php grid -->
    <link rel="stylesheet" href="gridlib/js/themes/redmond/jquery-ui.custom.css"></link>    
    <link rel="stylesheet" href="gridlib/js/jqgrid/css/ui.jqgrid.css"></link>   
    <script src="gridlib/js/jquery.min.js" type="text/javascript"></script>
    <script src="gridlib/js/jqgrid/js/i18n/grid.locale-ua.js" type="text/javascript"></script>
    <script src="gridlib/js/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript"></script>    
    <script src="gridlib/js/themes/jquery-ui.custom.min.js" type="text/javascript"></script>
    <!-- these css and js files are required by php grid -->

</head>
<body>
<center>
    <div style="margin:5px;width:96%;height:96%;text-align:center">

    <!-- display grid here -->
    <?php echo $out?>
    <!-- display grid here -->

    </div> 
</center>    
</body>
</html>