<?php
require_once "jusuite/jq-config.php";
// include the jqGrid Class
require_once "jusuite/php/jqGrid.php";
// include the PDO driver class
require_once "jusuite/php/jqGridPdo.php";
// Connection to the server
$conn = new PDO(DB_DSN,DB_USER,DB_PASSWORD);
// Tell the db that we use utf-8
$conn->query("SET NAMES utf8");
// Create the jqGrid instance
$grid = new jqGridRender($conn);
$grid->encoding = "utf-8";
// Write the SQL Query
$grid->SelectCommand = "
SELECT mcc.id AS mccid, LEFT(strip_tags(mcc.name),(POSITION((CHAR(0x28 USING utf8) COLLATE utf8_unicode_ci) IN strip_tags(mcc.name)))-1) COLLATE utf8_general_ci AS mccname, c.id AS cid, c.fullname AS cfullname, concat('<a target=\"_new\" href=\"http://moodle.tdmu.edu.ua/course/view.php?id=',c.id,' \">Link</a>') AS idlink,  
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
GROUP BY c.id
";
// set the ouput format to json
$grid->dataType = 'json';
// Let the grid create the model
$grid->setColModel();
// Set the url from where we obtain the data
$grid->setUrl('jusuite_rescountgrid.php');
// Set grid caption using the option caption
$grid->setGridOptions(array(
    "caption"=>"Кількість ресурсів по курсах. Сумарно та по типах - тести, посилання, файли, папки, контрольні списки, розклади",
    "autowidth"=>true, // expand grid to screen width
//    "shrinkToFit"=>"true",
    "height"=>"100%",
    "hidegrid"=>"true",
    "viewrecords" => true,
    "rowNum"=>20,
    "sortname"=>"cid",
    "rowList"=>array(20,30,50),
//    "footerrow"=>true,  
//    "userDataOnFooter"=>true, //grandtotal   
    "footerrow"=>true,
    "userDataOnFooter"=>true,    
        "grouping"=>true, // Enable grouping
        "groupingView"=>array(   // grouping options
        "groupField" => array('mccname'),   // group by field
        "groupColumnShow" => array(true),   // show the grouped column
        "groupText" =>array('<b>{0} - {1} дисциплін(а)</b>'),  // Bold the text at header 
        "groupDataSorted" => true,  // Tell the grid that it should sort the data on server in the appropriatre way
        "groupSummary" => array(true),  // Allow summary footer to place a varios summary info
        "groupCollapse" => false,
        "showSummaryOnHide" => false,
        "groupOrder" => array("asc"))
    ));
// add navigator with the default properties
$grid->navigator = true;
$grid->setNavOptions('navigator',array('add'=>false, 'edit'=>false, 'del'=>false));
// Enable filter toolbar searching
$grid->toolbarfilter = true;
$grid->setFilterOptions(array("searchOnEnter"=>false));
// Change some property of the field(s)
$grid->setColProperty("mccid", array("label"=>"К_каф.", "search"=>true, "width"=>50, "align"=>"center"));
$grid->setColProperty("mccname", array("label"=>"Кафедра", "search"=>true, "resizable"=>true));
$grid->setColProperty("cfullname", array("label"=>"Назва дисципліни", "resizable"=>true, "search"=>true));
$grid->setColProperty("cid", array("label"=>"Код", "width"=>50, "align"=>"center"));
$grid->setColProperty("idlink", array("label"=>"Лінк", "width"=>50, "search"=>false, "align"=>"center"));
$grid->setColProperty("sections", array("label"=>"Тем(занять)", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("quizes", array("label"=>"Тестів", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("resources", array("label"=>"Файлів", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("resources_pdf", array("label"=>"Файлів PDF", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("folders", array("label"=>"Папок", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("files_in_folders", array("label"=>"Файлів в папках", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("pdf_in_folders", array("label"=>"PDF в папках", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("ppt_in_folders", array("label"=>"PPT в папках", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("urls", array("label"=>"Посилань", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("checklists", array("label"=>"Матрикулів", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
$grid->setColProperty("schedulers", array("label"=>"Розкладів", "width"=>100, "search"=>false, "align"=>"center", "summaryType"=>"sum", "summaryTpl"=>'<b>Всього: {0}</b>'));
//$summaryrows = array("quizes"=>array("quizes"=>"SUM"), "sections"=>array("sections"=>"SUM"));
// Run the script
$grid->renderGrid('#grid','#pager',true, null, null, true,true);
//$grid->renderGrid('#grid','#pager',true, $summaryrows, null, true,true);
?>