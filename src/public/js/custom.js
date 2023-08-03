$(function() {

    $('#create-event-form #dateTimeStart').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        minYear: 2023,
        maxYear: parseInt(moment().format('YYYY'),10),
        startDate: moment().startOf('hour').add(60, 'minutes'),
        endDate: moment().startOf('hour').add(1, 'hour'),
        locale: {
            format: 'dddd, MMM DD HH:mm'
        }
    });

    $('#create-event-form #dateTimeEnd').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        minYear: 2023,
        maxYear: parseInt(moment().format('YYYY'),10),
        startDate: moment().startOf('hour').add(90, 'minutes'),
        locale: {
            format: 'dddd, MMM DD HH:mm'
        }
    });

    $('#create-event-form #timeZone').val(Intl.DateTimeFormat().resolvedOptions().timeZone);

    $('#eventGuests').selectize({
        create: true,
        valueField: "value",
        labelField: "name",
        sortField: "name",
        searchField: "name",
        delimiter: ",",
        selectOnTab: false,
        onItemAdd: function (){
            $('#eventGuests-selectized').focus();
        }
    });
});


function showSpinner(targetDiv,show=true){
    let spinner = '<div class="text-center spinner-border" role="status">' +
                      '<span class="visually-hidden"></span>' +
                  '</div>';
    if(show){
        $('#' + targetDiv).html(spinner);
    }else{
        $('#' + targetDiv).html('');
    }
}

function fetchEvents(linkKey, linkValue){
    showSpinner('eventsList');
    saveForm("get-events-form",'/google/events/list/'+linkKey+'/'+linkValue,"eventsList");
}