var maximumInventory = 13300;
var mediumInventory = 12400;
var minimumInventory = 11700;
var optimalInventory = 13100;

function test(){
    window.open('http://172.22.11.18/sbs/reports/histhold.php?old_first=1&run=0','winname','directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=400,height=350');
}

function filter(type){
    $('.pressList').hide();

    if(type == 'ALL')
        $('.kanbanRow').show();
    else if(type == 'TRAD')
    {
        $('.kanbanRow').show();
        $('.Krupp').hide();
        $('.PLT').hide();
    }
    else
    {
        $('.kanbanRow').hide();
        $(type).show();
    }
}

$(document).ready(function(){

    function getData ()
    {

        $.getJSON('php/kanbanData.php',function(jasonData){
            for(i=0;i<Object.keys(jasonData).length;i++)
            {
                if(!$('#'+jasonData[i].gt).html())
                {
                    var actCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].moldQty*jasonData[i].curePerHour));
                    var actCurCoverageText= jasonData[i].moldQty >0 ? Math.trunc(actCurCoverage) + ':' + Math.round((actCurCoverage*60)%60) : '';

                    var realCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].realMoldQty*jasonData[i].realCurePerHour));
                    var realCurCoverageText= jasonData[i].realMoldQty >0 ?  Math.trunc(realCurCoverage) + ':' + Math.round((realCurCoverage*60)%60) : '';

                    var plannedCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].plannedMoldQty*jasonData[i].plannedCurePerHour));
                    var plannedCurCoverageText= jasonData[i].plannedMoldQty >0 ? Math.trunc(plannedCurCoverage) + ':' + Math.round((plannedCurCoverage*60)%60) : '';

                    $('#kanbanTable').append('<tr value="1" class="kanbanRow '+jasonData[i].buildingGroup+' '+(jasonData[i].buildingMachines == null ? 'warning' : ' ')+'" id="'+jasonData[i].gt+'"><td>'+jasonData[i].gt+'</td>' +
                        '<td id="actCoverage" class="'+(actCurCoverage > 8 ? 'success' : (actCurCoverage > 3 ? 'warning' : 'danger' ))+'">'+actCurCoverageText+'</td>' + //pokrycie wulkanizacji wg zazbrojenia w RFBC
                        '<td id="realCoverage"  class="'+(realCurCoverage > 8 ? 'success' : (realCurCoverage > 3 ? 'warning' : 'danger' ))+'">'+realCurCoverageText+'</td>' + //pokrycie wulkanizacji wg aktualnego stanu
                        '<td id="plannedCoverage"  class="'+(plannedCurCoverage > 8 ? 'success' : (plannedCurCoverage > 3 ? 'warning' : 'danger' ))+'">'+plannedCurCoverageText+'</td>' + //pokrycie wulkanizacji wg planu
                        '<td>'+jasonData[i].moldQty+'/'+jasonData[i].realMoldQty+'/'+jasonData[i].plannedMoldQty+'</td>' +  // ilość form
                        '<td style="text-align: left;">'+jasonData[i].buildingMachines+'</td>' +
                        '<td>'+jasonData[i].buildingInvOk+'('+jasonData[i].buildingInvNok+')</td>' +
                        '<td>'+jasonData[i].moldsNoTires+'</td>' +
                        '<td style="display:none"></td>' +
                        '</tr>');
                }

                $('#kanbanTable').append('<tr value="1" class="pressList" id="'+jasonData[i].gt+'presslist" style="display:none; text-align: left;">' +
                    '<td style="display:none">A</td>' +
                    '<td style="display:none"></td>' +
                    '<td style="display:none"></td>' +
                    '<td colspan="9">'+jasonData[i].pressess+'</td>' +
                    '<td style="display:none"></td>' +
                    '<td style="display:none"></td>' +
                    '</tr>');
            }
            //CLICK ON KANBAN ROW - SHOW PRESSESS
            $('.kanbanRow').on("click", function (){
                $(this).next().toggle();
            });
        });

        //MAIN TABLE FILL
        $.getJSON('php/buildingData.php',function(jasonData){
            $('#buildingOutputForecast').html(jasonData.bc3output + '(' + jasonData.bc3pred + ')');
            $('#curingOutputForecast').html(jasonData.bc4output + '(' + jasonData.bc4pred + ')');
            $('#inventory').html(jasonData.inv_ok + '(' + jasonData.inv_nok + ')');
            $('#lackTires').html(jasonData.totalNoTires + ' FH');


            //TOTAL NO TIRES COLOR
            if(jasonData.totalNoTires > 50)
                $('#lackTires').attr('class','danger');
            else if(jasonData.totalNoTires > 25)
                $('#lackTires').attr('class','warning');
            else
                $('#lackTires').attr('class','success');

            //NO TRANSPORT COLOR
            if(jasonData.bc3Notires > 35)
                $('#bc3NoTires').attr('class','danger');
            else if(jasonData.bc3Notires > 20)
                $('#bc3NoTires').attr('class','warning');
            else
                $('#bc3NoTires').attr('class','success');
            $('#bc3NoTires').html(jasonData.bc3NoTires);

            //NO TRANSPORT COLOR
            if(jasonData.seitoNoTires > 15)
                $('#seitoNoTires').attr('class','danger');
            else if(jasonData.seitoNoTires > 10)
                $('#seitoNoTires').attr('class','warning');
            else
                $('#seitoNoTires').attr('class','success');
            $('#seitoNoTires').html(jasonData.seitoNoTires);

            if(jasonData.inv_ok <=minimumInventory || jasonData.inv_ok>=maximumInventory)
                $('#inventoryDiff').attr('class','danger');
            else if(jasonData.inv_ok<maximumInventory && jasonData.inv_ok >= mediumInventory)
                $('#inventoryDiff').attr('class','success');
            else
                $('#inventoryDiff').attr('class','warning');
            $('#inventoryDiff').html(jasonData.inv_ok-optimalInventory);


            if((jasonData.bc4pred - jasonData.bc4shiftPlan)>0)
                $('#bc4Realization').attr('class','success');
            else
                $('#bc4Realization').attr('class','danger');
            $('#bc4Realization').html((jasonData.bc4pred - jasonData.bc4shiftPlan));

            if(jasonData.bc3pred > jasonData.bc4shiftPlan + 100 && jasonData.bc3pred>jasonData.bc4pred)
                $('#bc3VsBc4').attr('class','success');
            else if(jasonData.bc3pred > jasonData.bc4shiftPlan)
                $('#bc3VsBc4').attr('class','warning');
            else
                $('#bc3VsBc4').attr('class','danger');

            $('#bc3Realization').html(jasonData.bc3pred - jasonData.bc3plan);
            $('#bc3VsBc4').html(jasonData.bc3pred - jasonData.bc4pred)
        });
        //INFORMATION ABOUT UPDATE
        var updateDate = new Date();
        $("#updateInfo").html(updateDate.getUTCFullYear()
            +'-'+updateDate.getUTCMonth()
            +'-'+updateDate.getUTCDate()+' '
            +updateDate.getUTCHours()+':'
            +updateDate.getUTCMinutes()+':'
            +updateDate.getUTCSeconds());
    }
    setInterval(getData,300000);
    getData();

});

