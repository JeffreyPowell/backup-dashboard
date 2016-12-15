<?php

# load local config file
$backuporgfile = '/var/www/html/backups/orgsdev.json';
$backupnodefile = '/var/www/html/backups/nodesdev.json';
$backupdir = '/backups';
#$backupdir = '/usr/local/bin/backups/data';

$backuporgjson = file_get_contents($backuporgfile);
$backuporglist = json_decode($backuporgjson, true);

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
echo "<td align='center'><font face='helvetica' color='#888888' size='2'>".date('d / m / Y').' '.date('H:i:s T').'</font>';
date_default_timezone_set('Europe/London');
echo "<br><br><font face='helvetica' color='#444444' size='2'>".date('d / m / Y').' '.date('H:i:s T').'</font>';
date_default_timezone_set('Europe/Berlin');
echo "<br><font face='helvetica' color='#444444' size='2'>".date('d / m / Y').' '.date('H:i:s T').'</font></td>';
echo '</tr>';
echo '</table>';

echo '<br>';

$dir = '/backups';
#$dir = '/tmp';
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
echo "<div bgcolor='#000000' align='center'><font face='helvetica' color='#666666' size='4'> $dir : $sizegb GB total, $usedgb GB used, $freegb GB free, $pcent used. </font></td>";
echo '<td></td>';
echo '</tr>';
echo '<td></td>';
echo "<div bgcolor='#000000' align='center'><img src='images/usage-all.php'></td>";
echo '<td></td>';
echo '</tr>';
echo '</table>';

echo '<br>';

echo "<table border='0' width='100%' cellpadding='4'>";
echo "<col width='10%'><col width='40%'><col width='10%'><col width='10%'><col width='10%'><col width='10%'><col width='10%'>";

foreach ($backuporglist['orgs'] as $org) {
    $org_name = $org['name'];

    echo '<tr>';
    echo "<td bgcolor='#666666'colspan='2' align='left'><font face='helvetica' color='#EEEEEE' size='4'>&nbsp$org_name</font></td>";
    echo '</tr>';

    echo '<tr>';
    echo '<td>&nbsp</td><td colspan=5>';

    foreach ($backupnodelist['nodes'] as $node) {
        if ($node['org'] == $org['name']) {

            #echo "\n\n**\n\n";

            $node_name = $node['server'];
            $node_path = $node['dir'];
            $node_status = $node['status'];
            $file_mask = $node['mask'];

            $path = $node_path.'/'.$file_mask;
            $test = $node['max_age_hours'];

            $allfiles = glob($path);

            array_multisort(array_map('filemtime', $allfiles), SORT_NUMERIC, SORT_DESC, $allfiles);

            $file_count = count($allfiles);

            if ($file_count == 0) {
                $newhoursold = '-';
                $olddaysold = '-';
                $allfiles = '';
            } else {
                $now = new DateTime();

                $newfiledatestr = date('Y-m-d H:i:s.', filemtime($allfiles[0]));
                $newfiledatetime = strtotime($newfiledatestr);

                $newfiledatetime = new DateTime('@'.strtotime($newfiledatestr));
                $newdiff = $now->diff($newfiledatetime);
                $newhoursold = (int) $newdiff->format('%h') + ((int) $newdiff->format('%a') * 24);

                $oldfiledatestr = date('Y-m-d H:i:s.', filemtime($allfiles[ $file_count - 1 ]));
                $oldfiledatetime = strtotime($oldfiledatestr);

                $oldfiledatetime = new DateTime('@'.strtotime($oldfiledatestr));
                $olddiff = $now->diff($oldfiledatetime);
                $olddaysold = (int) $olddiff->format('%a');
            }

            if ($node_status == 'inactive') {
                echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#444444; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#666666' size='1'>$node_name</font><BR><font face='helvetica' color='#666666' size='4'>inactive</font><br></div></a>";
            } else {
                if ($node_status == 'active' && $file_count == 0) {
                    echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#444444; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#FFFF00' size='1'>$node_name</font><BR><font face='helvetica' color='#FFFF00' size='4'>waiting</font></div></a>";
                } else {
                    if ($newhoursold > $test) {
                        echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#800000; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#FF0000' size='1'>$node_name</font><BR><font face='helvetica' color='#FF0000' size='4'>FAIL</font></div></a>";
                    } else {
                        echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#008000; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#00FF00' size='1'>$node_name</font><BR><font face='helvetica' color='#00FF00' size='4'>-OK-</font></div></a>";
                        #echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#008000; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#00FF00' size='1'>$node_name</font><BR><font face='helvetica' color='#00FF00' size='4'>-OK-</font></div></a>";
                        #echo "<a href='detail.php?=$node_name'><div style='display:inline-block; background-color:#008000; border:5px solid black; text-align:center; width:120px; height:36px; float:left'><font face='helvetica' color='#00FF00' size='1'>$node_name</font><BR><font face='helvetica' color='#00FF00' size='4'>-OK-</font></div></a>";
                                                                }
                }
            }
        }
    }

    echo '</td></tr>';

}
echo '</table>';
echo '<br><br>';
echo '</body></html>';

?>
