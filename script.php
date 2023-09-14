<?php
// we want to see any errors this script produces
error_reporting(E_ALL);

// analyze this save file
// example:
//$save_file = "C:\Users\Vincent\AppData\Local\Introversion\Prison Architect\saves\Reformation Center.prison";
$save_file = "";

if (!$save_file) {
    die("Please configure the script to point to a .prison save file.");
}

// the entire prison save file in a string
$file_content = file_get_contents($save_file);

$prisoner_strings = array();
findPrisoners($file_content);

$prisoners = array();
foreach($prisoner_strings as $prisoner_str) {
    $prisoner = new Prisoner;
    
    preg_match("/Forname[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->forname = $matches[1];
    
    preg_match("/Surname[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->surname = $matches[1];
    
    preg_match("/Category[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->category = $matches[1];

    preg_match("/Served[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->sentence_served = round(floatval($matches[1]),2);

    preg_match("/SentenceF[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->sentence_total = intval(@$matches[1]);

    if (str_contains($prisoner_str, "Type Alcohol  ActionPoint")) {
        $prisoner->addiction .= " alcohol ";
    }
    
    if (str_contains($prisoner_str, "Type Drugs  ActionPoint")) {
        $prisoner->addiction .= " drugs ";
    }
    
    preg_match("/WorkCook[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->experience['cooking'] = intval(floatval(@$matches[1])*100,0);
    
    preg_match("/WorkCleaner[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->experience['cleaning'] = intval(floatval(@$matches[1])*100,0);
    
    preg_match("/WorkCraftsman[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->experience['craftsmanship'] = intval(floatval(@$matches[1])*100,0);
    
    preg_match("/WorkLabourer[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->experience['labouring'] = intval(floatval(@$matches[1])*100,0);
   
    preg_match("/WorkRCS[\s]+([\S]+)/", $prisoner_str, $matches);
    $prisoner->experience['customer_service'] = intval(floatval(@$matches[1])*100,0);

    $prisoners[] = $prisoner;
    
}

// sorting prisoners by category
$prisoners_sorted = array(
    'Protected' => array(),
    'MinSec'    => array(),
    'Normal'    => array(),  // medium security
    'MaxSec'    => array(),
    'SuperMax'  => array(),
);
// loop through the prisoners
foreach($prisoners as $prisoner) {
    // using the default category names as array index to add prisoners to it.
    $prisoners_sorted[ $prisoner->category ][] = $prisoner;
}

// defining the prisoner objects
class Prisoner {
    public $forname;
    public $surname;
    public $category;
    public $addiction;
    public $sentence_served;
    public $sentence_total;
    public $experience = array(
        'cleaning'         => 0, 
        'cooking'          => 0, 
        'customer_service' => 0,
        'labouring'        => 0,
        'craftsmanship'    => 0,
    );
}

function findPrisoners($file_content) {
    global $prisoner_strings;
    $pos = strpos($file_content, "Type                 Prisoner");
    $pos_begin = strripos(substr($file_content, 0, $pos), "    BEGIN");  
    $matches = array();
    $re = "/^ {4}BEGIN[\S\s]+\bType[\s]+\bPrisoner\b[\S\s]+^ {4}END[\r\n]/Um";
    preg_match($re, $file_content, $matches, 0, $pos_begin);
    if (isset($matches[0])) {
        $prisoner_strings[] = $matches[0];
        $offset = $pos_begin + strlen($matches[0]);
        findPrisoners(substr($file_content, $offset));
    }    
}
?>
<html>
<head>
<script type="text/javascript" src="jquery-3.7.1.min.js"></script>
<script type="text/javascript">
    function mySort(category, subcategory) {

        var tbody = $('#tbody_' + category);
        tbody.find('tr').sort(function(a, b) {
            if ($('#sort_order').val()=='asc') {
                return $('td.'+subcategory+':last', a).text().localeCompare($('td.'+subcategory+':last', b).text(), 'en', {numeric: true});
            } else {
                return $('td.'+subcategory+':last', b).text().localeCompare($('td.'+subcategory+':last', a).text(), 'en', {numeric: true});
            }
        }).appendTo(tbody);

        var sort_order = $('#sort_order').val();
        
        if (sort_order=="asc") {
            document.getElementById("sort_order").value="desc";
        }
        if (sort_order=="desc") {
            document.getElementById("sort_order").value="asc";
        }
    }
    </script>
</head>
<body>
<input type="hidden" id="sort_order" value="asc">
<?php 
// lets print the prisoners 
foreach ($prisoners_sorted as $category => $prisoners) {

?>
<br>
<table id="table1" width="100%" border="1">
<?php
    echo "<tr><th colspan='9'>" . $category . " (" . count($prisoners) . ") </th></tr>";
?>
<tr>
    <th width='150px'>Name</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'cooking');">Cooking</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'cleaning');">Cleaning</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'craftsmanship');">Craftsmanship</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'labouring');">Labouring</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'customer_service');">Customer Service</th>
    <th>Addiction</th>
    <th>Sentence</th>
    <th style="cursor: pointer; text-decoration:underline;" onclick="mySort('<?php echo $category ?>', 'served');">% Served</th>
</tr>

<tbody id="tbody_<?php echo $category ?>">
<?php
    foreach ($prisoners as $prisoner) {

    // calculate time served as a percentage
    $pct_served = round( ($prisoner->sentence_served / $prisoner->sentence_total) * 100, 0);

    echo "
        <tr>
            <td>" . $prisoner->surname . ", " . $prisoner->forname . "</td>
            <td class='cooking'>" . $prisoner->experience['cooking'] . "</td>
            <td class='cleaning'>" . $prisoner->experience['cleaning'] . "</td>
            <td class='craftsmanship'>" . $prisoner->experience['craftsmanship'] . "</td>
            <td class='labouring'>" . $prisoner->experience['labouring'] . "</td>
            <td class='customer_service'>" . $prisoner->experience['customer_service'] . "</td>
            <td>" . $prisoner->addiction . "</td>
            <td>" . $prisoner->sentence_served . "/" . $prisoner->sentence_total . "</td>
            <td class='served'>" . $pct_served . "%</td>
        </tr>";
    } // close prisoners loop
?>
</tbody>
</table>
<?php 
} // close category loop
?>
</body>
<html>