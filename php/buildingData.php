<?php
/**
 * Created by PhpStorm.
 * User: AA62543
 * Date: 18-01-12
 * Time: 11:29
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
    $dateRange="to_date('".$start."  06:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  13:56:00','yy-mm-dd hh24:mi:ss') ";
    $startHr=06;
    $strat_hr2=6;
    $endHr=14;
    $shift=1;
    $shift_start="to_date('".$start."  06:00:00','yy-mm-dd hh24:mi:ss') ";
    $shiftEnd="to_date('".$end."  14:00:00','yy-mm-dd hh24:mi:ss') ";
    $start_plan_konf=new DateTime();
    $start_plan_konf=date_format ( $start_plan_konf, 'Ymd');
}

if($h>=14 && $h<22)
{
    $dateRange="to_date('".$start."  14:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  21:56:00','yy-mm-dd hh24:mi:ss') ";
    $shift_start="to_date('".$start."  14:00:00','yy-mm-dd hh24:mi:ss') ";
    $shiftEnd="to_date('".$end."  22:00:00','yy-mm-dd hh24:mi:ss') ";
    $startHr=14;
    $endHr=22;
    $shift=2;

    $start_plan_konf=new DateTime();
    $start_plan_konf=date_format ( $start_plan_konf, 'Ymd');
}

if($h>=22 || $h<6)
{
    if($h>=22)
    {
        $end=new DateTime();
        $end->add(new DateInterval('P1D'));
        $end=date_format ( $end, 'y-m-d');
        $dateRange="to_date('".$start."  22:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  05:56:00','yy-mm-dd hh24:mi:ss') ";
        $shift_start="to_date('".$start."  22:00:00','yy-mm-dd hh24:mi:ss') ";
        $shiftEnd="to_date('".$end."  06:00:00','yy-mm-dd hh24:mi:ss') ";
        $start_plan_konf=new DateTime();
        $start_plan_konf=date_format ($start_plan_konf, 'Ymd');
    }
    if($h<=6)
    {
        $start=new DateTime();
        $start->sub(new DateInterval('P1D'));
        $start=date_format ( $start, 'y-m-d');
        $dateRange="to_date('".$start."  22:05:00','yy-mm-dd hh24:mi:ss') and to_date('".$end. "  05:56:00','yy-mm-dd hh24:mi:ss') ";
        $shift_start="to_date('".$start."  22:00:00','yy-mm-dd hh24:mi:ss') ";
        $shiftEnd="to_date('".$end."  06:00:00','yy-mm-dd hh24:mi:ss') ";
        $start_plan_konf=new DateTime();
        $start_plan_konf->sub(new DateInterval('P1D'));
        $start_plan_konf=date_format ( $start_plan_konf, 'Ymd');
    }
    $startHr=22;
    $endHr=6;
    $shift=3;
}

// CURRENT RESULTS DATA GATHERING - OUTPUT&PREDICTION BOTH BUILDING&CURING
$stidCurResults = oci_parse($ffmesConnection,"select output,pred,plan_excel_total,prod,inv_ok,inv_nok,bc3_output,bc3_pred,bc3_schedule,transport,total,PLAN_EXCEL_1,PLAN_EXCEL_2,PLAN_EXCEL_3 from
                                            (
                                                select * from cur_pred left join
                                                cur_schedule on trunc(sysdate- interval '6' hour,'DDD')=DT and 1=1
                                                left join
                                                (
                                                    select sum(qty) bc3_schedule from 
                                                    schedule@bld join machines@bld on machine=name
                                                    where
                                                    sched_date='".$start_plan_konf."' and shift in (".$shift.") and code like 'PL-GT0%' and group_id_00='WBR'
                                                )on 1=1
                                                left join
                                                (
                                                    select round(count(case when spflags=0 then 1 else 0 end)/60,2) total,round(sum(case when spflags=0 then 1 else 0 end)/60,2) TRANSPORT
                                                    from cure_prod_down_notires@curemes
                                                    where 
                                                    dstamp between ".$dateRange."
                                                ) on 1=1
                                                where obszar='TOTAL' and dstamp between ".$dateRange." order by dstamp desc
                                            ) where rownum=1");
oci_execute($stidCurResults);

//PUTTING DATA INTO ARRAY WHICH WILL BE TRANSPONED TO JSON AND SENT BACK AS A RESPONSE
while ($row = oci_fetch_array($stidCurResults, OCI_BOTH))
{
    $responseData=array('bc4pred' => $row[1], 'bc4output' => $row[0], 'bc4plan' => $row[2],'inv_ok' => $row[4], 'inv_nok' => $row[5], 'bc3output' => $row[6], 'bc3pred' => $row[7], 'bc3plan' => $row[8], 'transport' => $row[9], 'building' => $row[10], 'avgPress' =>  $row[3] );
    if($shift==1)
        $responseData['bc4shiftPlan']=$row[11];
    if($shift==2)
        $responseData['bc4shiftPlan']=$row[12];
    if($shift==3)
        $responseData['bc4shiftPlan']=$row[13];
}

//COLLECTING DATA ABOUT CURRENT LACK OF TIRES AMOUNT
$stidNoTires = oci_parse($ffmesConnection, $text="select 
                                                    nvl(trunc(sum((down_edt-down_sdt)*24),2),0) SUMA,
                                                    nvl(TOTAL,0),
                                                    nvl(BT3,0), nvl(TRANSPORT,0), KRUPP, PLT, TRAD,KA
                                                    from
                                                    (
                                                        select 
                                                          resrce,
                                                          case when down_sdt< to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') then to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') else down_sdt end down_sdt,
                                                          case when down_edt>to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss') then to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss') else down_edt end down_edt,
                                                          case when cure_prod_down.down_code like '0%' then '98000' else cure_prod_down.down_code end down_code
                                                        from cure_prod_down@curemes
                                                        left join down_codes@curemes on cure_prod_down.down_code=down_codes.down_code
                                                        where 
                                                        resrce not like 'R%' and 
                                                        down_sdt!=down_edt and
                                                        (
                                                          down_sdt between to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') and to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss')
                                                          or down_edt between to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') and to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss')
                                                          or (down_sdt <= to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') and down_edt >= to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss'))
                                                        ) and cure_prod_down.down_code='16100'
                                                        UNION ALL
                                                        select 
                                                          cure_prod_down.resrce, 
                                                          case when down_sdt<to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') then to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') else down_sdt end down_sdt,
                                                          SYSDATE down_edt,
                                                          case when cure_prod_down.down_code like '0%' then '98000' else cure_prod_down.down_code end down_code 
                                                        from
                                                        cure_prod_down@curemes
                                                        left join machines@curemes on cure_prod_down.resrce=machines.resrce
                                                        where down_sdt=down_edt and machines.resrce not like 'R%' and status='D' and last_down_date = down_sdt
                                                        and 
                                                        (
                                                          down_sdt between to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss') and to_date('".$end." ".$endHr.":00:00','yy-mm-dd hh24:mi:ss')
                                                          or (down_sdt <= to_date('".$start." ".$startHr.":00:00','yy-mm-dd hh24:mi:ss'))
                                                        ) and cure_prod_down.down_code='16100'
                                                    ) S1
                                                    left join down_codes@curemes on S1.down_code = down_codes.down_code
                                                    left join 
                                                    (
                                                        select 
                                                        nvl(round(count(spflags)/60,2),0) TOTAL,
                                                        nvl(round(sum(case when spflags=1 then 1 else 0 end)/60,2),0) BT3,
                                                        nvl(round(sum(case when spflags=0 then 1 else 0 end)/60,2),0) TRANSPORT,
                                                        nvl(round(sum(case when spflags=1 and grupa='Krupp'  then 1 else 0 end)/60,2),0) KRUPP,
                                                        nvl(round(sum(case when spflags=1 and grupa='PLT' then 1 else 0 end)/60,2),0) PLT,
                                                        nvl(round(sum(case when spflags=1 and grupa='TRAD' then 1 else 0 end)/60,2),0) TRAD,
                                                        nvl(round(sum(case when spflags=1 and grupa is null then 1 else 0 end)/60,2),0) KA
                                                        from BUILD_NOTIRES_SAMPLING
                                                        left join 
                                                        (
                                                            select  code, case when group_id in ('Krupp', 'PLT') then group_id else 'TRAD' end grupa, sum(qty)
                                                            from schedule@bld left join machines@bld on machine = name
                                                            where  sched_date='20180222' and shift = 3 and code like 'PL-GT%' and group_id_00 != 'MRT'
                                                            group by code, case when group_id in ('Krupp', 'PLT') then group_id else 'TRAD' end
                                                        ) s1 on gtc=code
                                                        where 
                                                        dstamp between to_date('18-02-22 22:00:00', 'yy-mm-dd hh24:mi:ss') and to_date('18-02-23 06:00:00', 'yy-mm-dd hh24:mi:ss') and press not like 'R%'
                                                    ) on 1=1
                                                    group by TOTAL,BT3, TRANSPORT order by round(sum((down_edt-down_sdt)*24),2)  desc");

if(oci_execute($stidNoTires))
{
    $row = oci_fetch_row($stidNoTires);
    $responseData['totalNoTires'] = $row[0];
    $responseData['bc3NoTires'] = $row[2];
    $responseData['seitoNoTires'] = $row[3];
}
else{
    $responseData['totalNoTires'] = 0;
    $responseData['bc3NoTires'] = 0;
    $responseData['seitoNoTires'] = 0;
}


//JSON RESPONSE
echo json_encode($responseData,JSON_NUMERIC_CHECK);
?>