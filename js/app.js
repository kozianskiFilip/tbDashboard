var maximumInventory = 13300;
var mediumInventory = 12400;
var minimumInventory = 11700;
var optimalInventory = 13100;

function test(){
    alert('AAA');
    $("#mainPage").load('http://pldpk/bc3/buildingdayreport/dayreport.php');
};

$(document).ready(function(){

    $.getJSON('php/kanbanData.php',function(jasonData){
       for(i=0;i<Object.keys(jasonData).length;i++)
       {
           if(!$('#'+jasonData[i].gt).html())
           {
               var actCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].moldQty*jasonData[i].curePerHour));
                    actCurCoverage= Math.trunc(actCurCoverage) + ':' + Math.round((actCurCoverage*60)%60);

               var realCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].realMoldQty*jasonData[i].realCurePerHour));
               realCurCoverage= Math.trunc(realCurCoverage) + ':' + Math.round((realCurCoverage*60)%60);

               var plannedCurCoverage = (jasonData[i].buildingInvOk/(jasonData[i].plannedMoldQty*jasonData[i].plannedCurePerHour));
               plannedCurCoverage= Math.trunc(plannedCurCoverage) + ':' + Math.round((plannedCurCoverage*60)%60);

               $('#kanbanTable').append('<tr value="1" class="kanbanRow" id="'+jasonData[i].gt+'"><td>'+jasonData[i].gt+'</td>' +
                   '<td id="actCoverage">'+actCurCoverage+'</td>' + //pokrycie wulkanizacji wg zazbrojenia w RFBC
                   '<td id="actCoverage">'+realCurCoverage+'</td>' + //pokrycie wulkanizacji wg aktualnego stanu
                   '<td id="actCoverage">'+plannedCurCoverage+'</td>' + //pokrycie wulkanizacji wg planu
                   '<td>'+jasonData[i].moldQty+'/'+jasonData[i].realMoldQty+'/'+jasonData[i].plannedMoldQty+'</td>' +  // ilość form
                   '<td>'+jasonData[i].buildingMachines+'</td>' +
                   '<td>'+jasonData[i].buildingInvOk+'('+jasonData[i].buildingInvNok+')</td>' +
                   '<td></td>' +
                   '<td style="display:none"></td>' +
                   '</tr>');
           }

           $('#kanbanTable').append('<tr value="1" id="'+jasonData[i].gt+'presslist" style="">' +
               '<td style="display:none">A</td>' +
               '<td style="display:none"></td>' +
               '<td style="display:none"></td>' +
               '<td colspan="6" ;">'+jasonData[i].pressess+'</td>' +
               '<td style="display:none"></td>' +
               '<td style="display:none"></td>' +
               '</tr>');
       }
    });

    //CLICK ON KANBAN ROW - SHOW PRESSESS
    $('.kanbanRow').on("click", function (){
        alert('XXX');
        $(this).next().toggle();
       // alert($(this).next().html());
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



});

