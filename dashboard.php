<?php

# load local config file
$backupnodefile = '/var/www/html/backups/nodes.json';
$backupdir = '/backups';
#$backupdir = '/usr/local/bin/backups/data';

$backupnodejson = file_get_contents($backupnodefile);
$backupnodelist = json_decode($backupnodejson, true);

echo '<html><head>';
echo "<META HTTP-EQUIV='refresh' CONTENT='300'>";
echo "</head><body bgcolor='#000000'>";

echo "<table border='0' width='100%' cellpadding='0'>";
echo "<col width='25%'><col width='50%'><col width='25%'>";
echo '<tr>';
echo "<td align='left'>";
echo "<img src='/images/rakuten-clear.png' style='width:225px;height:56px;'>";
echo '</td>';
echo "<td align='center'>";
echo "<font face='helvetica' color='#EEEEEE' size='6'>";
echo '<strong>EU Ops Backups report</strong>';
echo '</font>';
echo '</td>';
date_default_timezone_set('UTC');
echo "<td align='left'><font face='helvetica' color='#888888' size='2'>".date("d / m / Y")." ".date("H:i:s T")."</font>";
date_default_timezone_set('Europe/London');
echo "<br><br><font face='helvetica' color='#444444' size='2'>".date("d / m / Y")." ".date("H:i:s T")."</font>";
date_default_timezone_set('Europe/Berlin');
echo "<br><font face='helvetica' color='#444444' size='2'>".date("d / m / Y")." ".date("H:i:s T")."</font></td>";
echo '</tr>';
echo '</table>';

echo '<br>';

$dir = '/backups';
$free = disk_free_space($dir);
$pcent = shell_exec("df -P | grep $dir | awk '{print $5}'");
#$pcent = 100-$pcent;
$freegb = shell_exec("df -PBG | grep $dir | awk '{print $4}'");
$usedgb = shell_exec("df -PBG | grep $dir | awk '{print $3}'");
$sizegb = shell_exec("df -PBG | grep $dir | awk '{print $2}'");

echo "<table border='0' width='100%' cellpadding='0'>";
echo "<col width='10%'><col width='80%'><col width='10%'>";
echo '<tr>';
echo '<td></td>';
echo "<td bgcolor='#000000' align='center'><font face='helvetica' color='#666666' size='4'> $dir : $sizegb GB total, $usedgb GB used, $freegb GB free, $pcent used. </font></td>";
echo '<td></td>';
echo '</tr>';
echo '<td></td>';
echo "<td bgcolor='#000000' align='center'><img src='images/usage-all.php'></td>";
echo '<td></td>';
echo '</tr>';
echo '</table>';

echo '<br>';

foreach ($backupnodelist['backups'] as $node_org) {
    $org_name = $node_org['org'];

    echo "<table border='0' width='100%' cellpadding='4'>";
    echo "<col width='10%'><col width='40%'><col width='10%'><col width='10%'><col width='10%'><col width='10%'><col width='10%'>";
    echo '<tr>';
    echo "<td bgcolor='#666666'colspan='2' align='left'><font face='helvetica' color='#EEEEEE' size='5'>&nbsp$org_name</font></td>";
    echo "<td bgcolor='#666666' align='center'><font face='helvetica' color='#EEEEEE' size='1'>count</font></td>";
    echo "<td bgcolor='#666666' align='center'><font face='helvetica' color='#EEEEEE' size='1'>oldest<br>[days]</font></td>";
    echo "<td bgcolor='#666666' align='center'><font face='helvetica' color='#EEEEEE' size='1'>newest<br>[hours]</font></td>";
    echo "<td bgcolor='#666666' align='center'><font face='helvetica' color='#AAAAAA' size='1'>max newest<br>[hours]</font></td>";
    echo "<td bgcolor='#666666' align='center'><font face='helvetica' color='#EEEEEE' size='1'>status</font></td>";
    echo '</tr>';

    foreach ($node_org['nodes'] as $node) {
        $node_name = $node['server'];
        $node_path = $node['dir'];
        $node_status = $node['status'];
        $file_mask = $node['mask'];

        $path = $node_path.'/*.*';
        $test = $node['max_age_hours'];

        $allfiles = glob($path);

        array_multisort( array_map( 'filemtime', $allfiles ), SORT_NUMERIC, SORT_DESC, $allfiles );

        $file_count = count($allfiles);

        if ($file_count == 0) {
            $newhoursold = '-'; $olddaysold ='-'; $allfiles ='';
        } else {
            $now = new DateTime();

            $newfiledatestr = date('Y-m-d H:i:s.', filemtime($allfiles[0]));
            $newfiledatetime = strtotime($newfiledatestr);

            $newfiledatetime = new DateTime('@'.strtotime($newfiledatestr));
            $newdiff = $now->diff($newfiledatetime);
            $newhoursold = (int)$newdiff->format('%h')+((int)$newdiff->format('%a')*24);

            $oldfiledatestr = date('Y-m-d H:i:s.'



            , filemtime($allfiles[ $file_count-1 ]));
            $oldfiledatetime = strtotime($oldfiledatestr);

            $oldfiledatetime = new DateTime('@'.strtotime($oldfiledatestr));
            $olddiff = $now->diff($oldfiledatetime);
            $olddaysold = (int)$olddiff->format('%a');
        }

        echo "<tr>";
        echo "<td bgcolor='#000000'></td>";
        echo "<td bgcolor='#444444' align='left'  ><a href='detail.php?=$node_name'><font face='helvetica' color='#EEEEEE' size='2'>$node_name</font></a></td>";
        echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#EEEEEE' size='2'>$file_count</font></td>";
        echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#EEEEEE' size='2'>$olddaysold</font></td>";
        echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#EEEEEE' size='2'>$newhoursold</font></td>";
        echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#666666' size='2'>$test</font></td>";

        if ($node_status == 'inactive') {
            echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#666666' size='2'>inactive</font></td>";
        } else {
            if ($node_status == 'active' && $file_count == 0) {
                echo "<td bgcolor='#444444' align='center'><font face='helvetica' color='#FFFF00' size='2'>waiting</font></td>";
            } else {
                if ($newhoursold > $test) {
                    echo "<td bgcolor='#800000' align='center'><font face='helvetica' color='#FF0000' size='2'>FAIL</font></td>";
                } else {
                    echo "<td bgcolor='#008000' align='center'><font face='helvetica' color='#00FF00' size='2'>-OK-</font></td>";
                }
            }
        }

        echo '</tr>';
    }

    echo '</table>';
    echo '<br><br>';
}

echo '</body></html>';

?>
