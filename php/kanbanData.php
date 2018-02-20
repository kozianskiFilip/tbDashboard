<?php
/**
 * Created by PhpStorm.
 * User: AA62543
 * Date: 18-01-17
 * Time: 19:45
 */

//error_reporting(E_ALL | E_STRICT);
//ini_set('display_errors', 1);
$ffmesConnection = oci_connect('minimes_ff_wbr', 'Baza0racl3appl1cs', '172.22.8.47/ORA');
$h=date('H');

$start=new DateTime();
$x=$start;
$start=date_format ( $start, 'Y-m-d');
$end=new DateTime();
$end=date_format ( $end, 'Y-m-d');
$scraps_table='';
$startHr;
$endHr;
$shift=1;

if($h>=6 && $h<14)
{
    $date_range="to_date('".$start."  06:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  13:56:00','yy-mm-dd hh24:mi:ss') ";
    $startHr=06;
    $startHr2=6;
    $endHr=14;
    $shift=1;
    $shift_start="to_date('".$start."  06:00:00','yy-mm-dd hh24:mi:ss') ";
    $shiftEnd="to_date('".$end."  14:00:00','yy-mm-dd hh24:mi:ss') ";
    $startBuilding=new DateTime();
    $startBuilding=date_format ( $startBuilding, 'Ymd');
}

if($h>=14 && $h<22)
{
    $date_range="to_date('".$start."  14:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  21:56:00','yy-mm-dd hh24:mi:ss') ";
    $shift_start="to_date('".$start."  14:00:00','yy-mm-dd hh24:mi:ss') ";
    $shiftEnd="to_date('".$end."  22:00:00','yy-mm-dd hh24:mi:ss') ";
    $startHr=14;
    $endHr=22;
    $shift=2;

    $startBuilding=new DateTime();
    $startBuilding=date_format ( $startBuilding, 'Ymd');
}

if($h>=22 || $h<6)
{
    if($h>=22)
    {
        $end=new DateTime();
        $end->add(new DateInterval('P1D'));
        $end=date_format ( $end, 'y-m-d');
        $date_range="to_date('".$start."  22:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  05:56:00','yy-mm-dd hh24:mi:ss') ";
        $shift_start="to_date('".$start."  22:00:00','yy-mm-dd hh24:mi:ss') ";
        $shiftEnd="to_date('".$end."  06:00:00','yy-mm-dd hh24:mi:ss') ";
        $startBuilding=new DateTime();
        $startBuilding=date_format ($startBuilding, 'Ymd');
    }
    if($h<=6)
    {
        $start=new DateTime();
        $start->sub(new DateInterval('P1D'));
        $start=date_format ( $start, 'y-m-d');
        $date_range="to_date('".$start."  22:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  05:56:00','yy-mm-dd hh24:mi:ss') ";
        $shift_start="to_date('".$start."  22:00:00','yy-mm-dd hh24:mi:ss') ";
        $shiftEnd="to_date('".$end."  06:00:00','yy-mm-dd hh24:mi:ss') ";
        $startBuilding=new DateTime();
        $startBuilding->sub(new DateInterval('P1D'));
        $startBuilding=date_format ( $startBuilding, 'Ymd');
    }
    $startHr=22;
    $endHr=6;
    $shift=3;
}

//ZMIENNE
$gtCode='';
$i=0;
$l=1;
$avgCycleTime=0;
$data=array();

$stidKanban = oci_parse($ffmesConnection,
    "SELECT s1.*, 
       CASE 
         WHEN cur_molds_qty > 0 THEN inv_ok / ( sum_act_ct / sum_act_fh * 
                                                cur_molds_qty 
                                              ) 
         ELSE 30 
       END coverage 
FROM   (SELECT DISTINCT gtcode, 
                        press, 
                        curing_schedule, 
                        fh, 
                        cur_plan100, 
                        cur_plan90, 
                        CASE 
                          WHEN cur_cycle_time != 0 THEN cur_cycle_time 
                          WHEN cur_cycle_time = 0 
                               AND cur_plan100 != 0 THEN 
                          Round(fh * 60 / cur_plan100, 2) 
                        END                      cur_cycle_time, 
                        active, 
                        cur_last_prod, 
                        cur_last_down, 
                        cur_status, 
                        cur_last_dwn_code, 
                        CASE 
                          WHEN cur_status = 'D' THEN cur_dwn_desc 
                          ELSE 'PRODUKCJA' 
                        END                      PRESS_STATUS, 
                        cur_dwn_col, 
                        buil_schedule, 
                        buil_output, 
                        buil_machines, 
                        inv_ok, 
                        inv_nok, 
                        cur_molds_qty, 
                        buil_groups, 
                        buil_machines_qty, 
                        when_updated, 
                        SUM(CASE 
                              WHEN cur_last_prod < 60 
                                   OR ( ( (cur_last_dwn_code = '16100' and cur_status='D') 
                                            OR cur_status = 'P' ) 
                                         AND active = 1 ) THEN 1 
                              ELSE 0 
                            END) 
                          over ( 
                            PARTITION BY gtcode) REAL_PRESS_QTY, 
                        Count(DISTINCT press) 
                          over ( 
                            PARTITION BY gtcode) PLANNED_PRESS_QTY, 
                        SUM(cur_plan100) 
                          over ( 
                            PARTITION BY gtcode) SUM_PLANNED_PLAN, 
                        SUM(CASE 
                              WHEN cur_last_prod < 60 
                                   OR( ( (cur_last_dwn_code = '16100' and cur_status='D') 
                                            OR cur_status = 'P' ) 
                                         AND active = 1 ) THEN cur_plan100 
                              ELSE 0 
                            END) 
                          over ( 
                            PARTITION BY gtcode) SUM_REAL_PLAN, 
                        SUM(CASE 
                              WHEN active = 1 THEN cur_plan100 
                              ELSE 0 
                            END) 
                          over ( 
                            PARTITION BY gtcode) SUM_ACT_CT, 
                        SUM(CASE 
                              WHEN active = 1 THEN fh 
                              ELSE 0 
                            END) 
                          over ( 
                            PARTITION BY gtcode) SUM_ACT_FH, 
                        SUM(CASE 
                              WHEN cur_last_prod < 60 
                                   OR ( ( (cur_last_dwn_code = '16100' and cur_status='D')
                                            OR cur_status = 'P' ) 
                                         AND active = 1 ) THEN fh 
                              ELSE 0 
                            END) 
                          over ( 
                            PARTITION BY gtcode) SUM_REAL_FH, 
                        SUM(fh) 
                          over ( 
                            PARTITION BY gtcode) SUM_PLANNED_FH , sum(case when cur_status='D' and cur_last_dwn_code='16100' and active=1 then 1 else 0 end) over(partition by gtcode) count_notires
        FROM   build_kanban 
        WHERE  cur_plan100 > 0 
        ORDER  BY gtcode, 
                  active DESC, 
                  cur_plan100 DESC, 
                  press) s1 
ORDER  BY count_notires desc, coverage, 
          gtcode, 
          press");

oci_execute($stidKanban);

while ($row = oci_fetch_array($stidKanban, OCI_BOTH))
{
    if($gtCode != $row[0] || $i==0)
    {
        $data[$row[0]]=array('gt'=> $row[0],'pressess' => '<span class="hintTip" style="background-color:'.$row[13].';">'.$row[1].'<span class="hintText">'.$row[1].' - '.$row[0].'</br>'.$row[2].'</br>'.$row[12].'</br>'.$row[8].'min</span></span>',
            'FH' => $row[3], 'plan100' => $row[4], 'plan90' => $row[5],
            'moldQty' => $row[19], 'curePerHour' => round($row[27]/$row[28], 2),
            'realMoldQty' => $row[23], 'realCurePerHour' => round($row[26]/$row[29], 2),
            'plannedMoldQty' => $row[24], 'plannedCurePerHour' => round($row[25]/$row[30], 2),
            'builScheduled' => $row[14], 'buildingOutput' => $row[15], 'buildingInvOk' => $row[17],
            'buildingInvNok' => $row[18],  'buildingGroup' => $row[20],
            'activeBuildingMachines' => $row[21] ,'buildingMachines' => $row[16],
            'moldsNoTires' => $row[31]);
        $gtCode=$row[0];
    }
    else
    {
        $data[$gtCode]['pressess'].=($row[7]==0 ? ('<hr>') : ('') ).'<span class="hintTip" style="background-color:'.$row[13].';">'.$row[1].'<span class="hintText">'.$row[1].' - '.$row[0].'</br>'.$row[2].'</br>'.$row[12].'</br>'.$row[8].'min</span></span>';
    }

    $l++;
    $i++;
}
echo json_encode(array_values($data), JSON_HEX_QUOT | JSON_HEX_TAG);